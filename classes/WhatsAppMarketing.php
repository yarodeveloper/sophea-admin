<?php
/**
 * SOPHEA - WhatsApp Marketing Module
 * 
 * Handles marketing campaigns, credits management, and analytics
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/WhatsAppAPI.php';

class WhatsAppMarketing {
    private $db;
    private $whatsappAPI;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->whatsappAPI = new WhatsAppAPI();
    }
    
    /**
     * Get current credits information
     * Note: Meta doesn't provide a direct credits API, so we estimate based on usage
     * 
     * @return array Credits information
     */
    public function getCreditsInfo() {
        try {
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            
            // Get today's credits record
            $stmt = $this->db->prepare("
                SELECT * FROM whatsapp_credits 
                WHERE date = :date
            ");
            $stmt->execute([':date' => $today]);
            $todayRecord = $stmt->fetch();
            
            // Get monthly usage
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(credits_used) as total_used,
                    SUM(total_cost) as total_cost,
                    SUM(messages_sent) as total_messages
                FROM whatsapp_credits 
                WHERE date >= :month_start
            ");
            $stmt->execute([':month_start' => $monthStart]);
            $monthlyData = $stmt->fetch();
            
            // Get estimated available credits (this would need to be set manually or via API if available)
            // For now, we'll use a default or stored value
            $stmt = $this->db->prepare("
                SELECT credits_available 
                FROM whatsapp_credits 
                WHERE date = :date AND credits_available > 0
                ORDER BY date DESC 
                LIMIT 1
            ");
            $stmt->execute([':date' => $today]);
            $lastAvailable = $stmt->fetch();
            
            $availableCredits = $lastAvailable['credits_available'] ?? 10000; // Default fallback
            $usedToday = $todayRecord['credits_used'] ?? 0;
            $remaining = max(0, $availableCredits - ($monthlyData['total_used'] ?? 0));
            
            // Calculate percentage used
            $percentageUsed = $availableCredits > 0 ? (($monthlyData['total_used'] ?? 0) / $availableCredits) * 100 : 0;
            
            return [
                'available' => (int)$availableCredits,
                'used_today' => (int)$usedToday,
                'used_month' => (int)($monthlyData['total_used'] ?? 0),
                'remaining' => (int)$remaining,
                'percentage_used' => round($percentageUsed, 2),
                'total_cost_month' => (float)($monthlyData['total_cost'] ?? 0),
                'total_messages_month' => (int)($monthlyData['total_messages'] ?? 0),
                'last_updated' => $todayRecord['last_sync_at'] ?? null
            ];
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getCreditsInfo Error: " . $e->getMessage());
            return [
                'available' => 0,
                'used_today' => 0,
                'used_month' => 0,
                'remaining' => 0,
                'percentage_used' => 0,
                'total_cost_month' => 0,
                'total_messages_month' => 0,
                'error' => 'Error al obtener información de créditos'
            ];
        }
    }
    
    /**
     * Update credits for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $available Available credits
     * @param int $used Used credits
     * @param float $cost Total cost
     * @return bool Success
     */
    public function updateCredits($date, $available, $used, $cost = 0) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_credits 
                (date, credits_available, credits_used, credits_remaining, total_cost, last_sync_at)
                VALUES (:date, :available, :used, :remaining, :cost, NOW())
                ON DUPLICATE KEY UPDATE
                    credits_available = :available,
                    credits_used = :used,
                    credits_remaining = :remaining,
                    total_cost = :cost,
                    last_sync_at = NOW()
            ");
            
            $remaining = max(0, $available - $used);
            
            return $stmt->execute([
                ':date' => $date,
                ':available' => $available,
                ':used' => $used,
                ':remaining' => $remaining,
                ':cost' => $cost
            ]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateCredits Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get dashboard metrics
     * 
     * @return array Dashboard metrics
     */
    public function getDashboardMetrics() {
        try {
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('-7 days'));
            $monthStart = date('Y-m-01');
            
            // Messages sent today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM whatsapp_message_log 
                WHERE DATE(created_at) = :today 
                AND status IN ('sent', 'delivered', 'read', 'replied')
            ");
            $stmt->execute([':today' => $today]);
            $sentToday = $stmt->fetch()['count'] ?? 0;
            
            // Messages sent this week
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM whatsapp_message_log 
                WHERE created_at >= :week_start 
                AND status IN ('sent', 'delivered', 'read', 'replied')
            ");
            $stmt->execute([':week_start' => $weekStart]);
            $sentWeek = $stmt->fetch()['count'] ?? 0;
            
            // Messages sent this month
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM whatsapp_message_log 
                WHERE created_at >= :month_start 
                AND status IN ('sent', 'delivered', 'read', 'replied')
            ");
            $stmt->execute([':month_start' => $monthStart]);
            $sentMonth = $stmt->fetch()['count'] ?? 0;
            
            // Delivery rate (last 30 days)
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('delivered', 'read', 'replied') THEN 1 ELSE 0 END) as delivered
                FROM whatsapp_message_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status != 'pending'
            ");
            $stmt->execute();
            $deliveryData = $stmt->fetch();
            $deliveryRate = $deliveryData['total'] > 0 
                ? ($deliveryData['delivered'] / $deliveryData['total']) * 100 
                : 0;
            
            // Read rate (last 30 days)
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('read', 'replied') THEN 1 ELSE 0 END) as read_count
                FROM whatsapp_message_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status != 'pending'
            ");
            $stmt->execute();
            $readData = $stmt->fetch();
            $readRate = $readData['total'] > 0 
                ? ($readData['read_count'] / $readData['total']) * 100 
                : 0;
            
            // Reply rate (last 30 days)
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied
                FROM whatsapp_message_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status != 'pending'
            ");
            $stmt->execute();
            $replyData = $stmt->fetch();
            $replyRate = $replyData['total'] > 0 
                ? ($replyData['replied'] / $replyData['total']) * 100 
                : 0;
            
            // Active campaigns
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM whatsapp_campaigns 
                WHERE status IN ('scheduled', 'sending')
            ");
            $stmt->execute();
            $activeCampaigns = $stmt->fetch()['count'] ?? 0;
            
            // Pending campaigns
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM whatsapp_campaigns 
                WHERE status = 'scheduled' 
                AND scheduled_at > NOW()
            ");
            $stmt->execute();
            $pendingCampaigns = $stmt->fetch()['count'] ?? 0;
            
            return [
                'sent_today' => (int)$sentToday,
                'sent_week' => (int)$sentWeek,
                'sent_month' => (int)$sentMonth,
                'delivery_rate' => round($deliveryRate, 2),
                'read_rate' => round($readRate, 2),
                'reply_rate' => round($replyRate, 2),
                'active_campaigns' => (int)$activeCampaigns,
                'pending_campaigns' => (int)$pendingCampaigns
            ];
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getDashboardMetrics Error: " . $e->getMessage());
            return [
                'sent_today' => 0,
                'sent_week' => 0,
                'sent_month' => 0,
                'delivery_rate' => 0,
                'read_rate' => 0,
                'reply_rate' => 0,
                'active_campaigns' => 0,
                'pending_campaigns' => 0
            ];
        }
    }
    
    /**
     * Get recent activity
     * 
     * @param int $limit Number of activities to return
     * @return array Recent activities
     */
    public function getRecentActivity($limit = 10) {
        try {
            // Get recent campaigns
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.type,
                    c.status,
                    c.scheduled_at,
                    c.sent_at,
                    c.total_sent,
                    c.total_delivered,
                    c.created_at
                FROM whatsapp_campaigns c
                ORDER BY c.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $campaigns = $stmt->fetchAll();
            
            // Get recent messages with errors
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    phone_number,
                    status,
                    error_message,
                    created_at
                FROM whatsapp_message_log
                WHERE status = 'failed'
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            $errors = $stmt->fetchAll();
            
            return [
                'campaigns' => $campaigns,
                'errors' => $errors
            ];
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getRecentActivity Error: " . $e->getMessage());
            return [
                'campaigns' => [],
                'errors' => []
            ];
        }
    }
    
    /**
     * Get usage chart data
     * 
     * @param int $days Number of days to include
     * @return array Chart data
     */
    public function getUsageChartData($days = 30) {
        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            
            $stmt = $this->db->prepare("
                SELECT 
                    date,
                    credits_used,
                    messages_sent,
                    total_cost
                FROM whatsapp_credits
                WHERE date >= :start_date
                ORDER BY date ASC
            ");
            $stmt->execute([':start_date' => $startDate]);
            $data = $stmt->fetchAll();
            
            // Fill in missing dates with zeros
            $chartData = [];
            $currentDate = strtotime($startDate);
            $endDate = strtotime('today');
            
            $dataIndex = 0;
            while ($currentDate <= $endDate) {
                $dateStr = date('Y-m-d', $currentDate);
                
                if ($dataIndex < count($data) && $data[$dataIndex]['date'] == $dateStr) {
                    $chartData[] = [
                        'date' => $dateStr,
                        'credits' => (int)$data[$dataIndex]['credits_used'],
                        'messages' => (int)$data[$dataIndex]['messages_sent'],
                        'cost' => (float)$data[$dataIndex]['total_cost']
                    ];
                    $dataIndex++;
                } else {
                    $chartData[] = [
                        'date' => $dateStr,
                        'credits' => 0,
                        'messages' => 0,
                        'cost' => 0
                    ];
                }
                
                $currentDate = strtotime('+1 day', $currentDate);
            }
            
            return $chartData;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getUsageChartData Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log a message to the message log
     * 
     * @param array $messageData Message data
     * @return int|false Message log ID or false on failure
     */
    public function logMessage($messageData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_message_log 
                (campaign_id, lead_id, phone_number, message_type, template_name, message_text, 
                 status, message_id, cost, created_at)
                VALUES 
                (:campaign_id, :lead_id, :phone_number, :message_type, :template_name, :message_text,
                 :status, :message_id, :cost, NOW())
            ");
            
            return $stmt->execute([
                ':campaign_id' => $messageData['campaign_id'] ?? null,
                ':lead_id' => $messageData['lead_id'] ?? null,
                ':phone_number' => $messageData['phone_number'] ?? '',
                ':message_type' => $messageData['message_type'] ?? 'text',
                ':template_name' => $messageData['template_name'] ?? null,
                ':message_text' => $messageData['message_text'] ?? null,
                ':status' => $messageData['status'] ?? 'pending',
                ':message_id' => $messageData['message_id'] ?? null,
                ':cost' => $messageData['cost'] ?? 0
            ]) ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::logMessage Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update message status
     * 
     * @param string $messageId WhatsApp message ID
     * @param string $status New status
     * @param array $additionalData Additional data to update
     * @return bool Success
     */
    public function updateMessageStatus($messageId, $status, $additionalData = []) {
        try {
        $updateFields = ['status = :status'];
        $params = [':status' => $status, ':message_id' => $messageId];
        
        if (isset($additionalData['delivered_at'])) {
            $updateFields[] = 'delivered_at = :delivered_at';
            $params[':delivered_at'] = $additionalData['delivered_at'];
        }
        
        if (isset($additionalData['read_at'])) {
            $updateFields[] = 'read_at = :read_at';
            $params[':read_at'] = $additionalData['read_at'];
        }
        
        if (isset($additionalData['replied_at'])) {
            $updateFields[] = 'replied_at = :replied_at';
            $params[':replied_at'] = $additionalData['replied_at'];
        }
        
        if (isset($additionalData['error_message'])) {
            $updateFields[] = 'error_message = :error_message';
            $params[':error_message'] = $additionalData['error_message'];
        }
        
        $sql = "UPDATE whatsapp_message_log SET " . implode(', ', $updateFields) . 
               " WHERE message_id = :message_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateMessageStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new campaign
     * 
     * @param array $campaignData Campaign data
     * @return int|false Campaign ID or false on failure
     */
    public function createCampaign($campaignData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_campaigns 
                (name, type, template_name, message_text, status, scheduled_at, send_immediately,
                 respect_business_hours, exclude_weekends, filter_criteria, created_by)
                VALUES 
                (:name, :type, :template_name, :message_text, :status, :scheduled_at, :send_immediately,
                 :respect_business_hours, :exclude_weekends, :filter_criteria, :created_by)
            ");
            
            $scheduledAt = null;
            if (!empty($campaignData['scheduled_at'])) {
                $scheduledAt = $campaignData['scheduled_at'];
            }
            
            $status = $campaignData['send_immediately'] ? 'scheduled' : ($scheduledAt ? 'scheduled' : 'draft');
            
            $result = $stmt->execute([
                ':name' => $campaignData['name'],
                ':type' => $campaignData['type'],
                ':template_name' => $campaignData['template_name'] ?? null,
                ':message_text' => $campaignData['message_text'] ?? null,
                ':status' => $status,
                ':scheduled_at' => $scheduledAt,
                ':send_immediately' => $campaignData['send_immediately'] ?? false,
                ':respect_business_hours' => $campaignData['respect_business_hours'] ?? true,
                ':exclude_weekends' => $campaignData['exclude_weekends'] ?? false,
                ':filter_criteria' => json_encode($campaignData['filter_criteria'] ?? []),
                ':created_by' => $campaignData['created_by'] ?? null
            ]);
            
            if ($result) {
                $campaignId = $this->db->lastInsertId();
                
                // If send immediately, process recipients and send
                if ($campaignData['send_immediately']) {
                    $this->processCampaignRecipients($campaignId, $campaignData['filter_criteria'] ?? []);
                    $this->sendCampaign($campaignId);
                } elseif ($scheduledAt) {
                    // Process recipients for scheduled campaign
                    $this->processCampaignRecipients($campaignId, $campaignData['filter_criteria'] ?? []);
                }
                
                return $campaignId;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::createCampaign Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update campaign
     * 
     * @param int $campaignId Campaign ID
     * @param array $campaignData Campaign data
     * @return bool Success
     */
    public function updateCampaign($campaignId, $campaignData) {
        try {
            $updateFields = [];
            $params = [':id' => $campaignId];
            
            $allowedFields = ['name', 'type', 'template_name', 'message_text', 'status', 
                            'scheduled_at', 'send_immediately', 'respect_business_hours', 
                            'exclude_weekends', 'filter_criteria'];
            
            foreach ($allowedFields as $field) {
                if (isset($campaignData[$field])) {
                    if ($field === 'filter_criteria') {
                        $updateFields[] = "{$field} = :{$field}";
                        $params[":{$field}"] = json_encode($campaignData[$field]);
                    } else {
                        $updateFields[] = "{$field} = :{$field}";
                        $params[":{$field}"] = $campaignData[$field];
                    }
                }
            }
            
            if (empty($updateFields)) {
                return false;
            }
            
            $sql = "UPDATE whatsapp_campaigns SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateCampaign Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get campaign by ID
     * 
     * @param int $campaignId Campaign ID
     * @return array|false Campaign data or false
     */
    public function getCampaign($campaignId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM whatsapp_campaigns WHERE id = :id");
            $stmt->execute([':id' => $campaignId]);
            $campaign = $stmt->fetch();
            
            if ($campaign && $campaign['filter_criteria']) {
                $campaign['filter_criteria'] = json_decode($campaign['filter_criteria'], true);
            }
            
            return $campaign;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getCampaign Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all campaigns with pagination
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string $status Filter by status
     * @return array Campaigns
     */
    public function getCampaigns($limit = 50, $offset = 0, $status = null) {
        try {
            $sql = "SELECT * FROM whatsapp_campaigns";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            
            $campaigns = $stmt->fetchAll();
            
            // Decode filter_criteria for each campaign
            foreach ($campaigns as &$campaign) {
                if ($campaign['filter_criteria']) {
                    $campaign['filter_criteria'] = json_decode($campaign['filter_criteria'], true);
                }
            }
            
            return $campaigns;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getCampaigns Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process campaign recipients based on filter criteria
     * 
     * @param int $campaignId Campaign ID
     * @param array $filterCriteria Filter criteria
     * @return int Number of recipients added
     */
    public function processCampaignRecipients($campaignId, $filterCriteria) {
        try {
            // Use advanced filters method
            $leads = $this->getLeadsByAdvancedFilters($filterCriteria);
            
            // Insert recipients
            $inserted = 0;
            $insertStmt = $this->db->prepare("
                INSERT INTO whatsapp_campaign_recipients 
                (campaign_id, lead_id, phone_number, status)
                VALUES (:campaign_id, :lead_id, :phone_number, 'pending')
                ON DUPLICATE KEY UPDATE status = 'pending'
            ");
            
            foreach ($leads as $lead) {
                $phone = preg_replace('/[^0-9+]/', '', $lead['whatsapp']);
                $phone = ltrim($phone, '+');
                
                if (strlen($phone) >= 10) {
                    if ($insertStmt->execute([
                        ':campaign_id' => $campaignId,
                        ':lead_id' => $lead['id'],
                        ':phone_number' => $phone
                    ])) {
                        $inserted++;
                    }
                }
            }
            
            // Update total recipients count
            $updateStmt = $this->db->prepare("
                UPDATE whatsapp_campaigns 
                SET total_recipients = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = :id)
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $campaignId]);
            
            return $inserted;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::processCampaignRecipients Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send campaign messages
     * 
     * @param int $campaignId Campaign ID
     * @return array Results
     */
    public function sendCampaign($campaignId) {
        try {
            $campaign = $this->getCampaign($campaignId);
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }
            
            // Update campaign status
            $this->updateCampaign($campaignId, ['status' => 'sending', 'sent_at' => date('Y-m-d H:i:s')]);
            
            // Get pending recipients
            $stmt = $this->db->prepare("
                SELECT r.*, l.nombre, l.especialidad
                FROM whatsapp_campaign_recipients r
                JOIN leads l ON r.lead_id = l.id
                WHERE r.campaign_id = :campaign_id AND r.status = 'pending'
                LIMIT 100
            ");
            $stmt->execute([':campaign_id' => $campaignId]);
            $recipients = $stmt->fetchAll();
            
            $results = [
                'sent' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            foreach ($recipients as $recipient) {
                try {
                    // Prepare message
                    $message = $this->prepareMessage($campaign, $recipient);
                    
                    // Send message
                    if ($campaign['template_name']) {
                        // Send template message with type-specific parameters
                        $params = $this->extractTemplateParamsForType($campaign, $recipient, $campaign['template_name']);
                        $response = $this->whatsappAPI->sendTemplateMessage(
                            $recipient['phone_number'],
                            $campaign['template_name'],
                            $params,
                            'es'
                        );
                    } else {
                        // Send text message
                        $response = $this->whatsappAPI->sendMessage(
                            $recipient['phone_number'],
                            $message
                        );
                    }
                    
                    if ($response['success'] ?? false) {
                        // Update recipient status
                        $updateStmt = $this->db->prepare("
                            UPDATE whatsapp_campaign_recipients 
                            SET status = 'sent', message_id = :message_id, sent_at = NOW()
                            WHERE id = :id
                        ");
                        $updateStmt->execute([
                            ':id' => $recipient['id'],
                            ':message_id' => $response['message_id'] ?? null
                        ]);
                        
                        // Log message
                        $this->logMessage([
                            'campaign_id' => $campaignId,
                            'lead_id' => $recipient['lead_id'],
                            'phone_number' => $recipient['phone_number'],
                            'message_type' => $campaign['template_name'] ? 'template' : 'text',
                            'template_name' => $campaign['template_name'],
                            'message_text' => $message,
                            'status' => 'sent',
                            'message_id' => $response['message_id'] ?? null,
                            'cost' => 0.005 // Estimated cost per message
                        ]);
                        
                        $results['sent']++;
                    } else {
                        throw new Exception($response['error'] ?? 'Unknown error');
                    }
                    
                } catch (Exception $e) {
                    // Update recipient status to failed
                    $updateStmt = $this->db->prepare("
                        UPDATE whatsapp_campaign_recipients 
                        SET status = 'failed', error_message = :error, failed_at = NOW()
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':id' => $recipient['id'],
                        ':error' => $e->getMessage()
                    ]);
                    
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            }
            
            // Update campaign metrics
            $this->updateCampaignMetrics($campaignId);
            
            // Check if all sent
            $remainingStmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM whatsapp_campaign_recipients 
                WHERE campaign_id = :campaign_id AND status = 'pending'
            ");
            $remainingStmt->execute([':campaign_id' => $campaignId]);
            $remaining = $remainingStmt->fetch()['count'];
            
            if ($remaining == 0) {
                $this->updateCampaign($campaignId, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("WhatsAppMarketing::sendCampaign Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Prepare message with variables replaced
     * 
     * @param array $campaign Campaign data
     * @param array $recipient Recipient data
     * @return string Prepared message
     */
    private function prepareMessage($campaign, $recipient) {
        $message = $campaign['message_text'] ?? '';
        $campaignType = $campaign['type'] ?? 'personalizado';
        
        // Base variables
        $variables = [
            '{nombre}' => $recipient['nombre'] ?? '',
            '{especialidad}' => $recipient['especialidad'] ?? '',
        ];
        
        // Type-specific variables
        $filterCriteria = is_string($campaign['filter_criteria']) 
            ? json_decode($campaign['filter_criteria'], true) 
            : ($campaign['filter_criteria'] ?? []);
        
        switch ($campaignType) {
            case 'cita':
                $variables['{fecha_cita}'] = $filterCriteria['fecha_cita'] ?? '{fecha_cita}';
                $variables['{hora_cita}'] = $filterCriteria['hora_cita'] ?? '{hora_cita}';
                $variables['{doctor}'] = $filterCriteria['doctor'] ?? '{doctor}';
                $variables['{motivo}'] = $filterCriteria['motivo'] ?? '{motivo}';
                break;
                
            case 'cancelacion':
                $variables['{fecha_cita}'] = $filterCriteria['fecha_cita'] ?? '{fecha_cita}';
                $variables['{hora_cita}'] = $filterCriteria['hora_cita'] ?? '{hora_cita}';
                $variables['{doctor}'] = $filterCriteria['doctor'] ?? '{doctor}';
                $variables['{telefono}'] = $filterCriteria['telefono'] ?? '{telefono}';
                break;
                
            case 'promocion':
                $variables['{descuento}'] = $filterCriteria['descuento'] ?? '{descuento}';
                $variables['{oferta}'] = $filterCriteria['oferta'] ?? '{oferta}';
                $variables['{validez}'] = $filterCriteria['validez'] ?? '{validez}';
                break;
        }
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $message = str_replace($key, $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Get predefined templates by campaign type
     * 
     * @param string $type Campaign type
     * @return array Templates
     */
    public function getPredefinedTemplates($type) {
        $templates = [
            'cita' => [
                [
                    'name' => 'appointment_confirmation_1',
                    'display_name' => 'Confirmación de Cita',
                    'description' => 'Confirma una cita médica con todos los detalles',
                    'variables' => ['nombre_cliente', 'nombre_dr', 'motivo_cita', 'fecha', 'hora'],
                    'preview' => 'Buen día {nombre_cliente}, Gracias por reservar con {nombre_dr}. Se confirma su cita para {motivo_cita} el {fecha} a las {hora}. Gracias'
                ],
                [
                    'name' => 'recordatorio_cita',
                    'display_name' => 'Recordatorio de Cita',
                    'description' => 'Recordatorio simple de cita programada',
                    'variables' => [],
                    'preview' => 'Te recordamos que tu cita con el Dr. Méndez es:'
                ]
            ],
            'cancelacion' => [
                [
                    'name' => 'appointment_cancellation_1',
                    'display_name' => 'Cancelación de Cita',
                    'description' => 'Notifica la cancelación de una cita',
                    'variables' => ['nombre_cliente', 'nombre_dr', 'fecha', 'hora', 'telefono'],
                    'preview' => 'Buen día {nombre_cliente}, Tu próxima cita con {nombre_dr} el {fecha} a las {hora} ha sido cancelada. Háganos saber si tiene alguna pregunta o necesita reprogramarla al teléfono {telefono}. Gracias'
                ]
            ],
            'promocion' => [
                [
                    'name' => 'recordatorio',
                    'display_name' => 'Promoción General',
                    'description' => 'Plantilla para promociones y ofertas',
                    'variables' => ['fecha', 'hora'],
                    'preview' => 'Recordatorio: nuestro técnico visitará su ubicación el {fecha} a las {hora} para su instalación de banda ancha. Por favor, esté disponible.'
                ]
            ],
            'seguimiento' => [
                [
                    'name' => 'tes_unomedic',
                    'display_name' => 'Bienvenida/Seguimiento',
                    'description' => 'Mensaje de bienvenida o seguimiento',
                    'variables' => [],
                    'preview' => 'Hola, Bienvenido a UNOmedic'
                ]
            ]
        ];
        
        return $templates[$type] ?? [];
    }
    
    /**
     * Extract template parameters based on campaign type and template
     * 
     * @param array $campaign Campaign data
     * @param array $recipient Recipient data
     * @param string $templateName Template name
     * @return array Parameters for template
     */
    public function extractTemplateParamsForType($campaign, $recipient, $templateName) {
        $campaignType = $campaign['type'] ?? 'personalizado';
        $filterCriteria = is_string($campaign['filter_criteria']) 
            ? json_decode($campaign['filter_criteria'], true) 
            : ($campaign['filter_criteria'] ?? []);
        
        $params = [];
        
        // Base parameter (nombre)
        $nombre = $recipient['nombre'] ?? '';
        
        switch ($templateName) {
            case 'appointment_confirmation_1':
                $params = [
                    $nombre,
                    $filterCriteria['doctor'] ?? 'Dr. Méndez',
                    $filterCriteria['motivo'] ?? 'Consulta',
                    $filterCriteria['fecha_cita'] ?? date('d/m/Y'),
                    $filterCriteria['hora_cita'] ?? '10:00 AM'
                ];
                break;
                
            case 'appointment_cancellation_1':
                $params = [
                    $nombre,
                    $filterCriteria['doctor'] ?? 'Dr. Méndez',
                    $filterCriteria['fecha_cita'] ?? date('d/m/Y'),
                    $filterCriteria['hora_cita'] ?? '10:00 AM',
                    $filterCriteria['telefono'] ?? '555-1234'
                ];
                break;
                
            case 'recordatorio':
                $params = [
                    $filterCriteria['fecha_cita'] ?? date('d/m/Y'),
                    $filterCriteria['hora_cita'] ?? '10:00 AM'
                ];
                break;
                
            case 'recordatorio_cita':
            case 'tes_unomedic':
                // No parameters needed
                $params = [];
                break;
                
            default:
                // Try to extract from message text if available
                if (!empty($campaign['message_text'])) {
                    // Simple extraction - can be enhanced
                    $params = [$nombre];
                }
                break;
        }
        
        return $params;
    }
    
    
    /**
     * Update campaign metrics
     * 
     * @param int $campaignId Campaign ID
     * @return bool Success
     */
    public function updateCampaignMetrics($campaignId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE whatsapp_campaigns c
                SET 
                    c.total_sent = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('sent', 'delivered', 'read', 'replied')),
                    c.total_delivered = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('delivered', 'read', 'replied')),
                    c.total_read = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status IN ('read', 'replied')),
                    c.total_replied = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status = 'replied'),
                    c.total_failed = (SELECT COUNT(*) FROM whatsapp_campaign_recipients WHERE campaign_id = c.id AND status = 'failed')
                WHERE c.id = :id
            ");
            
            return $stmt->execute([':id' => $campaignId]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateCampaignMetrics Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get leads for segmentation
     * 
     * @param array $filters Filter criteria
     * @return array Leads
     */
    public function getLeadsForSegmentation($filters = []) {
        try {
            $sql = "SELECT id, nombre, especialidad, whatsapp, status, created_at FROM leads WHERE 1=1";
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['especialidad'])) {
                $sql .= " AND especialidad = :especialidad";
                $params[':especialidad'] = $filters['especialidad'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $sql .= " AND whatsapp IS NOT NULL AND whatsapp != ''";
            $sql .= " ORDER BY created_at DESC LIMIT 1000";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getLeadsForSegmentation Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get campaign recipients
     * 
     * @param int $campaignId Campaign ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Recipients
     */
    public function getCampaignRecipients($campaignId, $limit = 100, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, l.nombre, l.especialidad
                FROM whatsapp_campaign_recipients r
                JOIN leads l ON r.lead_id = l.id
                WHERE r.campaign_id = :campaign_id
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getCampaignRecipients Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete campaign
     * 
     * @param int $campaignId Campaign ID
     * @return bool Success
     */
    public function deleteCampaign($campaignId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM whatsapp_campaigns WHERE id = :id");
            return $stmt->execute([':id' => $campaignId]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::deleteCampaign Error: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // FASE 3: SEGMENTACIÓN AVANZADA
    // ============================================
    
    /**
     * Get all tags
     * 
     * @return array Tags
     */
    public function getAllTags() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM whatsapp_lead_tags ORDER BY name ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getAllTags Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new tag
     * 
     * @param string $name Tag name
     * @param string $color Tag color (hex)
     * @param string $description Description
     * @return int|false Tag ID or false
     */
    public function createTag($name, $color = '#667eea', $description = '') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_lead_tags (name, color, description)
                VALUES (:name, :color, :description)
            ");
            
            if ($stmt->execute([
                ':name' => $name,
                ':color' => $color,
                ':description' => $description
            ])) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::createTag Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete tag
     * 
     * @param int $tagId Tag ID
     * @return bool Success
     */
    public function deleteTag($tagId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM whatsapp_lead_tags WHERE id = :id");
            return $stmt->execute([':id' => $tagId]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::deleteTag Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign tag to lead
     * 
     * @param int $leadId Lead ID
     * @param int $tagId Tag ID
     * @return bool Success
     */
    public function assignTagToLead($leadId, $tagId) {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO whatsapp_lead_tag_assignments (lead_id, tag_id)
                VALUES (:lead_id, :tag_id)
            ");
            return $stmt->execute([
                ':lead_id' => $leadId,
                ':tag_id' => $tagId
            ]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::assignTagToLead Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove tag from lead
     * 
     * @param int $leadId Lead ID
     * @param int $tagId Tag ID
     * @return bool Success
     */
    public function removeTagFromLead($leadId, $tagId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM whatsapp_lead_tag_assignments
                WHERE lead_id = :lead_id AND tag_id = :tag_id
            ");
            return $stmt->execute([
                ':lead_id' => $leadId,
                ':tag_id' => $tagId
            ]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::removeTagFromLead Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get tags for a lead
     * 
     * @param int $leadId Lead ID
     * @return array Tags
     */
    public function getLeadTags($leadId) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.* FROM whatsapp_lead_tags t
                JOIN whatsapp_lead_tag_assignments a ON t.id = a.tag_id
                WHERE a.lead_id = :lead_id
            ");
            $stmt->execute([':lead_id' => $leadId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getLeadTags Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all contact lists
     * 
     * @return array Contact lists
     */
    public function getAllContactLists() {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, COUNT(m.id) as member_count
                FROM whatsapp_contact_lists l
                LEFT JOIN whatsapp_contact_list_members m ON l.id = m.list_id
                GROUP BY l.id
                ORDER BY l.created_at DESC
            ");
            $stmt->execute();
            $lists = $stmt->fetchAll();
            
            // Decode filter_criteria
            foreach ($lists as &$list) {
                if ($list['filter_criteria']) {
                    $list['filter_criteria'] = json_decode($list['filter_criteria'], true);
                }
            }
            
            return $lists;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getAllContactLists Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create contact list
     * 
     * @param string $name List name
     * @param string $description Description
     * @param array $filterCriteria Filter criteria
     * @return int|false List ID or false
     */
    public function createContactList($name, $description = '', $filterCriteria = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_contact_lists (name, description, filter_criteria)
                VALUES (:name, :description, :filter_criteria)
            ");
            
            if ($stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':filter_criteria' => json_encode($filterCriteria)
            ])) {
                $listId = $this->db->lastInsertId();
                
                // Process members based on filter criteria
                $this->processContactListMembers($listId, $filterCriteria);
                
                return $listId;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::createContactList Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update contact list
     * 
     * @param int $listId List ID
     * @param string $name List name
     * @param string $description Description
     * @param array $filterCriteria Filter criteria
     * @return bool Success
     */
    public function updateContactList($listId, $name, $description = '', $filterCriteria = []) {
        try {
            $stmt = $this->db->prepare("
                UPDATE whatsapp_contact_lists
                SET name = :name, description = :description, filter_criteria = :filter_criteria
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $listId,
                ':name' => $name,
                ':description' => $description,
                ':filter_criteria' => json_encode($filterCriteria)
            ]);
            
            if ($result) {
                // Reprocess members
                $this->processContactListMembers($listId, $filterCriteria);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateContactList Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete contact list
     * 
     * @param int $listId List ID
     * @return bool Success
     */
    public function deleteContactList($listId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM whatsapp_contact_lists WHERE id = :id");
            return $stmt->execute([':id' => $listId]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::deleteContactList Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process contact list members based on filter criteria
     * 
     * @param int $listId List ID
     * @param array $filterCriteria Filter criteria
     * @return int Number of members added
     */
    public function processContactListMembers($listId, $filterCriteria) {
        try {
            // First, clear existing members
            $deleteStmt = $this->db->prepare("DELETE FROM whatsapp_contact_list_members WHERE list_id = :list_id");
            $deleteStmt->execute([':list_id' => $listId]);
            
            // Get leads based on filter criteria
            $leads = $this->getLeadsByAdvancedFilters($filterCriteria);
            
            // Add members
            $insertStmt = $this->db->prepare("
                INSERT INTO whatsapp_contact_list_members (list_id, lead_id)
                VALUES (:list_id, :lead_id)
            ");
            
            $added = 0;
            foreach ($leads as $lead) {
                if ($insertStmt->execute([
                    ':list_id' => $listId,
                    ':lead_id' => $lead['id']
                ])) {
                    $added++;
                }
            }
            
            // Update member count
            $updateStmt = $this->db->prepare("
                UPDATE whatsapp_contact_lists SET total_contacts = :count WHERE id = :id
            ");
            $updateStmt->execute([
                ':id' => $listId,
                ':count' => $added
            ]);
            
            return $added;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::processContactListMembers Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get leads by advanced filters (including tags and lists)
     * 
     * @param array $filters Filter criteria
     * @return array Leads
     */
    public function getLeadsByAdvancedFilters($filters) {
        try {
            $sql = "SELECT DISTINCT l.* FROM leads l WHERE 1=1";
            $params = [];
            
            // Basic filters
            if (!empty($filters['status'])) {
                $sql .= " AND l.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['especialidad'])) {
                $sql .= " AND l.especialidad = :especialidad";
                $params[':especialidad'] = $filters['especialidad'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND l.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND l.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['source'])) {
                $sql .= " AND l.source = :source";
                $params[':source'] = $filters['source'];
            }
            
            // Tag filters
            if (!empty($filters['tags']) && is_array($filters['tags'])) {
                $tagIds = array_filter($filters['tags'], 'is_numeric');
                if (!empty($tagIds)) {
                    $placeholders = [];
                    foreach ($tagIds as $index => $tagId) {
                        $key = ':tag_' . $index;
                        $placeholders[] = $key;
                        $params[$key] = $tagId;
                    }
                    $sql .= " AND l.id IN (
                        SELECT lead_id FROM whatsapp_lead_tag_assignments 
                        WHERE tag_id IN (" . implode(',', $placeholders) . ")
                    )";
                }
            }
            
            // Exclude tags
            if (!empty($filters['exclude_tags']) && is_array($filters['exclude_tags'])) {
                $tagIds = array_filter($filters['exclude_tags'], 'is_numeric');
                if (!empty($tagIds)) {
                    $placeholders = [];
                    foreach ($tagIds as $index => $tagId) {
                        $key = ':exclude_tag_' . $index;
                        $placeholders[] = $key;
                        $params[$key] = $tagId;
                    }
                    $sql .= " AND l.id NOT IN (
                        SELECT lead_id FROM whatsapp_lead_tag_assignments 
                        WHERE tag_id IN (" . implode(',', $placeholders) . ")
                    )";
                }
            }
            
            // Contact list filter
            if (!empty($filters['contact_list_id'])) {
                $sql .= " AND l.id IN (
                    SELECT lead_id FROM whatsapp_contact_list_members 
                    WHERE list_id = :list_id
                )";
                $params[':list_id'] = $filters['contact_list_id'];
            }
            
            // Exclude contact list
            if (!empty($filters['exclude_list_id'])) {
                $sql .= " AND l.id NOT IN (
                    SELECT lead_id FROM whatsapp_contact_list_members 
                    WHERE list_id = :exclude_list_id
                )";
                $params[':exclude_list_id'] = $filters['exclude_list_id'];
            }
            
            // Days since contact
            if (!empty($filters['days_since_contact'])) {
                $sql .= " AND (l.updated_at IS NULL OR l.updated_at < DATE_SUB(NOW(), INTERVAL :days DAY))";
                $params[':days'] = $filters['days_since_contact'];
            }
            
            // Valid WhatsApp number
            $sql .= " AND l.whatsapp IS NOT NULL AND l.whatsapp != ''";
            $sql .= " AND LENGTH(REPLACE(REPLACE(REPLACE(l.whatsapp, '+', ''), ' ', ''), '-', '')) >= 10";
            
            $sql .= " ORDER BY l.created_at DESC LIMIT 10000";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getLeadsByAdvancedFilters Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Preview recipients count based on filters
     * 
     * @param array $filters Filter criteria
     * @return int Count
     */
    public function previewRecipientsCount($filters) {
        $leads = $this->getLeadsByAdvancedFilters($filters);
        return count($leads);
    }
    
    /**
     * Get contact list by ID
     * 
     * @param int $listId List ID
     * @return array|false List data or false
     */
    public function getContactList($listId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM whatsapp_contact_lists WHERE id = :id");
            $stmt->execute([':id' => $listId]);
            $list = $stmt->fetch();
            
            if ($list && $list['filter_criteria']) {
                $list['filter_criteria'] = json_decode($list['filter_criteria'], true);
            }
            
            return $list;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getContactList Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get contact list members
     * 
     * @param int $listId List ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Members
     */
    public function getContactListMembers($listId, $limit = 100, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.* FROM leads l
                JOIN whatsapp_contact_list_members m ON l.id = m.lead_id
                WHERE m.list_id = :list_id
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':list_id', $listId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getContactListMembers Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ============================================
    // GESTIÓN DE PLANTILLAS
    // ============================================
    
    /**
     * Get all custom templates
     * 
     * @param string $category Filter by category
     * @return array Templates
     */
    public function getAllCustomTemplates($category = null) {
        try {
            $sql = "SELECT * FROM whatsapp_templates_custom WHERE is_active = 1";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }
            
            $sql .= " ORDER BY category, name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $templates = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($templates as &$template) {
                if ($template['variables']) {
                    $template['variables'] = json_decode($template['variables'], true);
                }
                if ($template['example_data']) {
                    $template['example_data'] = json_decode($template['example_data'], true);
                }
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getAllCustomTemplates Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get template by ID
     * 
     * @param int $templateId Template ID
     * @return array|false Template data or false
     */
    public function getCustomTemplate($templateId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM whatsapp_templates_custom WHERE id = :id");
            $stmt->execute([':id' => $templateId]);
            $template = $stmt->fetch();
            
            if ($template) {
                if ($template['variables']) {
                    $template['variables'] = json_decode($template['variables'], true);
                }
                if ($template['example_data']) {
                    $template['example_data'] = json_decode($template['example_data'], true);
                }
            }
            
            return $template;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getCustomTemplate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create custom template
     * 
     * @param array $templateData Template data
     * @return int|false Template ID or false
     */
    public function createCustomTemplate($templateData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_templates_custom 
                (name, category, template_text, variables, example_data, is_active, requires_approval)
                VALUES 
                (:name, :category, :template_text, :variables, :example_data, :is_active, :requires_approval)
            ");
            
            $variables = !empty($templateData['variables']) ? json_encode($templateData['variables']) : null;
            $exampleData = !empty($templateData['example_data']) ? json_encode($templateData['example_data']) : null;
            
            if ($stmt->execute([
                ':name' => $templateData['name'],
                ':category' => $templateData['category'],
                ':template_text' => $templateData['template_text'],
                ':variables' => $variables,
                ':example_data' => $exampleData,
                ':is_active' => $templateData['is_active'] ?? true,
                ':requires_approval' => $templateData['requires_approval'] ?? false
            ])) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::createCustomTemplate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update custom template
     * 
     * @param int $templateId Template ID
     * @param array $templateData Template data
     * @return bool Success
     */
    public function updateCustomTemplate($templateId, $templateData) {
        try {
            $updateFields = [];
            $params = [':id' => $templateId];
            
            $allowedFields = ['name', 'category', 'template_text', 'variables', 'example_data', 'is_active', 'requires_approval'];
            
            foreach ($allowedFields as $field) {
                if (isset($templateData[$field])) {
                    if ($field === 'variables' || $field === 'example_data') {
                        $updateFields[] = "{$field} = :{$field}";
                        $params[":{$field}"] = json_encode($templateData[$field]);
                    } else {
                        $updateFields[] = "{$field} = :{$field}";
                        $params[":{$field}"] = $templateData[$field];
                    }
                }
            }
            
            if (empty($updateFields)) {
                return false;
            }
            
            $sql = "UPDATE whatsapp_templates_custom SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::updateCustomTemplate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete custom template
     * 
     * @param int $templateId Template ID
     * @return bool Success
     */
    public function deleteCustomTemplate($templateId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM whatsapp_templates_custom WHERE id = :id");
            return $stmt->execute([':id' => $templateId]);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::deleteCustomTemplate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all unique especialidades from leads
     * 
     * @return array Especialidades
     */
    public function getAllEspecialidades() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT especialidad 
                FROM leads 
                WHERE especialidad IS NOT NULL AND especialidad != ''
                ORDER BY especialidad ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getAllEspecialidades Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get lead statistics for dashboard
     * 
     * @return array Statistics
     */
    public function getLeadStatistics() {
        try {
            $stats = [];
            
            // Total leads
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM leads");
            $stmt->execute();
            $stats['total'] = $stmt->fetch()['total'] ?? 0;
            
            // Leads by status
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) as count 
                FROM leads 
                GROUP BY status
            ");
            $stmt->execute();
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Leads with valid WhatsApp
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM leads 
                WHERE whatsapp IS NOT NULL 
                AND whatsapp != '' 
                AND LENGTH(REPLACE(REPLACE(REPLACE(whatsapp, '+', ''), ' ', ''), '-', '')) >= 10
            ");
            $stmt->execute();
            $stats['with_whatsapp'] = $stmt->fetch()['count'] ?? 0;
            
            // Leads by source
            $stmt = $this->db->prepare("
                SELECT source, COUNT(*) as count 
                FROM leads 
                WHERE source IS NOT NULL
                GROUP BY source
            ");
            $stmt->execute();
            $stats['by_source'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Recent leads (last 30 days)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM leads 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['recent_30_days'] = $stmt->fetch()['count'] ?? 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log("WhatsAppMarketing::getLeadStatistics Error: " . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [],
                'with_whatsapp' => 0,
                'by_source' => [],
                'recent_30_days' => 0
            ];
        }
    }
}

