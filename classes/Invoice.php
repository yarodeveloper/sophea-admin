<?php
/**
 * SOPHEA - Invoice/Receipt Management Class
 *
 * Handles generation and management of invoices/receipts
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Client.php';
require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/Payment.php';
require_once __DIR__ . '/SiteSettings.php';

class Invoice {
    private $db;
    private $client;
    private $service;
    private $payment;
    private $siteSettings;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->client = new Client();
        $this->service = new Service();
        $this->payment = new Payment();
        $this->siteSettings = new SiteSettings();
    }

    /**
     * Generate invoice number
     * Format: {type}-{month}{year}-{sequential}
     * Example: web-122025-1
     */
    public function generateInvoiceNumber($serviceType = 'web', $year = null, $month = null) {
        if (!$year) $year = date('y');
        if (!$month) $month = date('m');
        
        // Get service type prefix (first 3 letters, lowercase)
        $prefix = strtolower(substr($serviceType, 0, 3));
        if (empty($prefix)) $prefix = 'gen';
        
        // Get last invoice number for this month and type
        $lastNumber = $this->getLastInvoiceNumber($prefix, $year, $month);
        $nextNumber = $lastNumber + 1;
        
        return $prefix . '-' . $month . $year . '-' . $nextNumber;
    }

    /**
     * Get last invoice number for a given prefix, year, and month
     */
    private function getLastInvoiceNumber($prefix, $year, $month) {
        try {
            $pattern = $prefix . '-' . $month . $year . '-%';
            $stmt = $this->db->prepare("
                SELECT invoice_number 
                FROM invoices 
                WHERE invoice_number LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$pattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['invoice_number'])) {
                // Extract number from invoice_number (e.g., "web-122025-5" -> 5)
                $parts = explode('-', $result['invoice_number']);
                if (count($parts) >= 3) {
                    return intval($parts[2]);
                }
            }
            
            return 0;
        } catch (PDOException $e) {
            error_log("Error getting last invoice number: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get invoice data for a client
     * @param int $clientId Client ID
     * @param int|null $serviceId Service ID (null for all services)
     * @param string|null $paymentDateLimit Date limit (Y-m-d) - only include payments up to this date
     * @return array Invoice data
     */
    public function getInvoiceData($clientId, $serviceId = null, $paymentDateLimit = null, $includeFinished = false) {
        try {
            // Get client data
            $clientData = $this->client->getClientById($clientId);
            if (!$clientData) {
                throw new Exception('Cliente no encontrado');
            }

            // Get company data (SOPHEA company info)
            $companyLogo = $this->siteSettings->getMainLogo();
            $companyAddress = $this->siteSettings->getSetting('company_address', defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : '');
            $companyPhone = $this->siteSettings->getSetting('company_phone', defined('CONTACT_PHONE') ? CONTACT_PHONE : '');
            $companyPhoneWhatsapp = $this->siteSettings->getSetting('company_phone_whatsapp', '');
            $companyPhoneLandline = $this->siteSettings->getSetting('company_phone_landline', '');
            $companyEmail = $this->siteSettings->getSetting('company_email', defined('CONTACT_EMAIL_PUBLIC') ? CONTACT_EMAIL_PUBLIC : '');
            $companyChatbot = $this->siteSettings->getSetting('company_chatbot', '');
            
            $companyData = [
                'name' => 'Sophea Marketing',
                'contact' => defined('DIRECTOR_NAME') ? DIRECTOR_NAME . ' - ' . $companyPhone : $companyPhone,
                'location' => defined('CONTACT_CITY') ? CONTACT_CITY : 'Tuxtla Gutiérrez',
                'address' => $companyAddress,
                'logo' => $companyLogo,
                'phone' => $companyPhone,
                'phone_whatsapp' => $companyPhoneWhatsapp,
                'phone_landline' => $companyPhoneLandline,
                'email' => $companyEmail,
                'chatbot' => $companyChatbot
            ];

            // Get services
            if ($serviceId) {
                $serviceData = $this->service->getServiceById($serviceId);
                $services = $serviceData ? [$serviceData] : [];
            } else {
                // Determine status filter
                $statusFilter = $includeFinished ? null : 'active';
                // Get services based on filter
                $services = $this->service->getServicesByClient($clientId, $statusFilter);
            }
            
            if (empty($services)) {
                throw new Exception('No se encontraron servicios activos para este cliente');
            }

            // Get payments
            $paymentFilters = ['client_id' => $clientId];
            if ($serviceId) {
                $paymentFilters['service_id'] = $serviceId;
            }
            
            $allPayments = $this->payment->getAllPayments($paymentFilters);
            
            // Filter payments to only include those belonging to the allowed services
            $allowedServiceIds = array_column($services, 'id');
            $allPayments = array_filter($allPayments, function($p) use ($allowedServiceIds) {
                // If service_id is null, it might be a general payment. 
                // However, in this system, most payments should be linked to a service.
                return in_array($p['service_id'], $allowedServiceIds);
            });
            
            // Filter payments by date limit if provided (for cumulative invoices)
            // Use paid_at if available, otherwise payment_date
            if ($paymentDateLimit) {
                $allPayments = array_filter($allPayments, function($p) use ($paymentDateLimit) {
                    $paymentDate = '';
                    if (!empty($p['paid_at'])) {
                        $paymentDate = date('Y-m-d', strtotime($p['paid_at']));
                    } elseif (!empty($p['payment_date'])) {
                        $paymentDate = $p['payment_date'];
                    }
                    return $paymentDate <= $paymentDateLimit;
                });
            }
            
            // Separate payments by status
            $paymentsMade = array_filter($allPayments, function($p) {
                return $p['status'] === 'paid';
            });
            
            $paymentsPending = array_filter($allPayments, function($p) {
                return in_array($p['status'], ['pending', 'overdue']);
            });

            // Calculate totals
            $serviceTotal = 0;
            $paidTotal = 0;
            $pendingTotal = 0;

            // Calculate total contracted amount from services
            foreach ($services as $svc) {
                $serviceTotal += floatval($svc['monthly_fee'] ?? 0);
            }
            
            foreach ($paymentsMade as $pay) {
                $paidTotal += floatval($pay['amount']);
            }
            
            // pendingTotal represents the remaining debt (Total Services - Paid)
            $pendingTotal = max(0, $serviceTotal - $paidTotal);
            
            // remainingTotal represents the current debt
            $remainingTotal = $pendingTotal;

            // Build service items for invoice - Detailed payment breakdown grouped by service
            $serviceItems = [];
            
            // Group payments by service
            $paymentsByService = [];
            // Initialize with all services to ensure they appear in the invoice
            foreach ($services as $svc) {
                $paymentsByService[$svc['id']] = [];
            }
            
            foreach ($allPayments as $pay) {
                if (in_array($pay['status'], ['paid', 'pending', 'overdue'])) {
                    $serviceId = $pay['service_id'] ?? null;
                    if (!isset($paymentsByService[$serviceId])) {
                        $paymentsByService[$serviceId] = [];
                    }
                    $paymentsByService[$serviceId][] = $pay;
                }
            }
            
            // For each service, add service name and its payments
            foreach ($paymentsByService as $serviceId => $servicePayments) {
                // Get service name
                $serviceName = 'Servicio';
                if ($serviceId) {
                    // First try to find in services array
                    foreach ($services as $svc) {
                        if ($svc['id'] == $serviceId) {
                            $serviceName = $svc['service_name'] ?? 'Servicio';
                            break;
                        }
                    }
                    // If not found, try to get from payment data (service_name is included in getAllPayments)
                    if ($serviceName === 'Servicio' && !empty($servicePayments)) {
                        $firstPayment = $servicePayments[0];
                        if (!empty($firstPayment['service_name'])) {
                            $serviceName = $firstPayment['service_name'];
                        } else {
                            // Last resort: fetch from database
                            $serviceData = $this->service->getServiceById($serviceId);
                            if ($serviceData) {
                                $serviceName = $serviceData['service_name'] ?? 'Servicio';
                            }
                        }
                    }
                } else {
                    // If no service_id, use first service name or generic
                    if (!empty($services)) {
                        $serviceName = $services[0]['service_name'] ?? 'Servicio';
                    } elseif (!empty($servicePayments)) {
                        // Try to get from payment data
                        $firstPayment = $servicePayments[0];
                        if (!empty($firstPayment['service_name'])) {
                            $serviceName = $firstPayment['service_name'];
                        }
                    }
                }
                
                // Sort payments by date (oldest first) for this service
                // Use paid_at if available, otherwise payment_date
                usort($servicePayments, function($a, $b) {
                    $dateA = '';
                    if (!empty($a['paid_at'])) {
                        $dateA = date('Y-m-d', strtotime($a['paid_at']));
                    } elseif (!empty($a['payment_date'])) {
                        $dateA = $a['payment_date'];
                    }
                    
                    $dateB = '';
                    if (!empty($b['paid_at'])) {
                        $dateB = date('Y-m-d', strtotime($b['paid_at']));
                    } elseif (!empty($b['payment_date'])) {
                        $dateB = $b['payment_date'];
                    }
                    
                    return strcmp($dateA, $dateB);
                });
                
                // Get service monthly fee
                $serviceMonthlyFee = 0;
                if ($serviceId) {
                    foreach ($services as $svc) {
                        if ($svc['id'] == $serviceId) {
                            $serviceMonthlyFee = floatval($svc['monthly_fee'] ?? 0);
                            break;
                        }
                    }
                }
                
                // Add service header (if multiple services or single service with name)
                if (count($paymentsByService) > 1 || !empty($serviceName)) {
                    $serviceItems[] = [
                        'description' => $serviceName,
                        'date' => '',
                        'quantity' => '',
                        'price' => $serviceMonthlyFee,
                        'total' => '',
                        'is_service_header' => true,
                        'service_id' => $serviceId
                    ];
                }
                
                // Add payment items for this service
                if (empty($servicePayments)) {
                    // Si no hay pagos para este servicio, agregar un item genérico pendiente para que aparezca en el PDF
                    if ($serviceMonthlyFee > 0) {
                        $serviceItems[] = [
                            'description'      => 'Cargo de Servicio — PENDIENTE',
                            'date'             => date('Y-m-d'),
                            'quantity'         => 1,
                            'price'            => $serviceMonthlyFee,
                            'total'            => $serviceMonthlyFee,
                            'service_id'       => $serviceId,
                            'is_service_header'=> false,
                            'status_class'     => 'pending',
                            'row_type'         => 'fee'
                        ];
                    }
                } else {
                    foreach ($servicePayments as $pay) {
                        $paymentDate = '';
                        if (!empty($pay['paid_at'])) {
                            $paymentDate = date('Y-m-d', strtotime($pay['paid_at']));
                        } elseif (!empty($pay['payment_date'])) {
                            $paymentDate = date('Y-m-d', strtotime($pay['payment_date']));
                        }
                        
                        // Determine label based on status
                        $statusLabel = '';
                        $statusClass = '';
                        if ($pay['status'] === 'pending') {
                            $statusLabel = ' — PENDIENTE';
                            $statusClass = 'pending';
                        } elseif ($pay['status'] === 'overdue') {
                            $statusLabel = ' — VENCIDO';
                            $statusClass = 'overdue';
                        } elseif ($pay['status'] === 'paid') {
                            $statusLabel = '';
                            $statusClass = 'paid';
                        }
                        
                        $feeAmount  = floatval($pay['fee_amount']  ?? 0);
                        $adsAmount  = floatval($pay['ads_amount']  ?? 0);
                        $totalAmount = floatval($pay['amount'] ?? 0);
                        
                        // If we have explicit fee/ads split, show as two separate rows
                        if ($feeAmount > 0 && $adsAmount > 0) {
                            // Row 1: Honorario / Feed (service fee)
                            $serviceItems[] = [
                                'description'      => 'Honorario de Servicio / Feed' . $statusLabel,
                                'date'             => $paymentDate,
                                'quantity'         => 1,
                                'price'            => $feeAmount,
                                'total'            => $feeAmount,
                                'service_id'       => $pay['service_id'],
                                'is_service_header'=> false,
                                'status_class'     => $statusClass,
                                'row_type'         => 'fee'
                            ];
                            // Row 2: Inversión ADS (platform investment)
                            $adsStatusLabel = $pay['status'] === 'paid' ? '' : $statusLabel;
                            $serviceItems[] = [
                                'description'      => 'Inversión en Plataforma ADS' . $adsStatusLabel,
                                'date'             => $paymentDate,
                                'quantity'         => 1,
                                'price'            => $adsAmount,
                                'total'            => $adsAmount,
                                'service_id'       => $pay['service_id'],
                                'is_service_header'=> false,
                                'status_class'     => $statusClass,
                                'row_type'         => 'ads_investment'
                            ];
                        } elseif ($feeAmount > 0 && $adsAmount == 0) {
                            // Only fee (no investment split)
                            $serviceItems[] = [
                                'description'      => 'Honorario de Servicio' . $statusLabel,
                                'date'             => $paymentDate,
                                'quantity'         => 1,
                                'price'            => $feeAmount,
                                'total'            => $feeAmount,
                                'service_id'       => $pay['service_id'],
                                'is_service_header'=> false,
                                'status_class'     => $statusClass,
                                'row_type'         => 'fee'
                            ];
                        } else {
                            // Generic payment row (no split data — old payments or non-ADS)
                            $serviceItems[] = [
                                'description'      => 'Pago de Servicio' . $statusLabel,
                                'date'             => $paymentDate,
                                'quantity'         => 1,
                                'price'            => $totalAmount,
                                'total'            => $totalAmount,
                                'service_id'       => $pay['service_id'],
                                'is_service_header'=> false,
                                'status_class'     => $statusClass,
                                'row_type'         => 'generic'
                            ];
                        }
                    }
                }
            }

            // Generate invoice number
            $serviceType = !empty($services) ? ($services[0]['service_type'] ?? 'web') : 'web';
            $invoiceNumber = $this->generateInvoiceNumber($serviceType);

            // Format client contact: contact_name + phone
            $clientContact = '';
            if (!empty($clientData['contact_name'])) {
                $clientContact = $clientData['contact_name'];
            }
            if (!empty($clientData['phone'])) {
                $clientContact .= ($clientContact ? ' ' : '') . $clientData['phone'];
            } elseif (!empty($clientData['whatsapp'])) {
                $clientContact .= ($clientContact ? ' ' : '') . $clientData['whatsapp'];
            }
            if (empty($clientContact)) {
                $clientContact = 'Sin contacto';
            }
            
            // Format client location: address + city
            $clientLocation = '';
            if (!empty($clientData['address'])) {
                $clientLocation = $clientData['address'];
            }
            if (!empty($clientData['city'])) {
                $clientLocation .= ($clientLocation ? ', ' : '') . $clientData['city'];
            }
            if (empty($clientLocation)) {
                $clientLocation = ($clientData['city'] ?? '') . ($clientData['state'] ? ', ' . $clientData['state'] : '');
            }
            if (empty($clientLocation)) {
                $clientLocation = 'Sin ubicación';
            }
            
            // Get service name(s) for total label
            $serviceNameForTotal = '';
            $serviceNamesList = [];
            $isMultipleServices = count($services) > 1;
            
            if (count($services) === 1) {
                $serviceNameForTotal = $services[0]['service_name'] ?? 'Servicio';
            } elseif ($isMultipleServices) {
                // Collect all service names
                foreach ($services as $svc) {
                    $svcName = $svc['service_name'] ?? 'Servicio';
                    if (!empty($svcName)) {
                        $serviceNamesList[] = $svcName;
                    }
                }
            }
            
            return [
                'invoice_number' => $invoiceNumber,
                'client_id' => $clientId,
                'service_id' => $serviceId,
                'date' => date('d/M/Y'),
                'client' => [
                    'name' => $clientData['company_name'] ?? $clientData['contact_name'] ?? 'Cliente',
                    'contact' => $clientContact,
                    'location' => $clientLocation
                ],
                'company' => $companyData,
                'service_name_for_total' => $serviceNameForTotal,
                'service_names_list' => $serviceNamesList,
                'is_multiple_services' => $isMultipleServices,
                'services' => $serviceItems,
                'totals' => [
                    'service_total' => $serviceTotal,
                    'paid_total' => $paidTotal,
                    'pending_total' => $pendingTotal,
                    'remaining_total' => $remainingTotal,
                    'iva' => 0 // Can be calculated if needed
                ],
                'payments_made' => array_values($paymentsMade),
                'payments_pending' => array_values($paymentsPending),
                'payment_date_limit' => $paymentDateLimit
            ];
        } catch (Exception $e) {
            error_log("Error getting invoice data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available services for a client (for dropdown)
     */
    public function getClientServices($clientId) {
        try {
            return $this->service->getServicesByClient($clientId, 'active');
        } catch (Exception $e) {
            error_log("Error getting client services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save invoice to history
     * @param array $invoiceData Invoice data from getInvoiceData()
     * @param string $format Format: 'html' or 'pdf'
     * @param string $invoiceUrl URL of the generated invoice
     * @param string $sendVia Method: 'whatsapp', 'email', or 'both'
     * @param string|null $recipientPhone Phone number
     * @param string|null $recipientEmail Email address
     * @param string|null $messageSent Message that was sent
     * @param string|null $filePath Path to PDF file if saved
     * @param int|null $createdBy User ID who created the invoice
     * @return int|false Invoice ID on success, false on failure
     */
    public function saveInvoiceHistory($invoiceData, $format = 'html', $invoiceUrl = null, $sendVia = 'whatsapp', 
                                       $recipientPhone = null, $recipientEmail = null, $messageSent = null, 
                                       $filePath = null, $createdBy = null) {
        try {
            // Validate required data
            if (!isset($invoiceData['invoice_number']) || !isset($invoiceData['client_id']) || !isset($invoiceData['totals'])) {
                error_log("Error: Missing required invoice data for saveInvoiceHistory");
                return false;
            }
            
            $invoiceType = isset($invoiceData['service_id']) && $invoiceData['service_id'] ? 'single_service' : 'all_services';
            $serviceId = isset($invoiceData['service_id']) ? $invoiceData['service_id'] : null;
            
            $stmt = $this->db->prepare("
                INSERT INTO invoices (
                    invoice_number, client_id, service_id, invoice_type,
                    total_amount, paid_amount, pending_amount, remaining_amount,
                    invoice_date, format, file_path, invoice_url,
                    sent_via, sent_at, recipient_phone, recipient_email,
                    message_sent, created_by
                ) VALUES (
                    :invoice_number, :client_id, :service_id, :invoice_type,
                    :total_amount, :paid_amount, :pending_amount, :remaining_amount,
                    :invoice_date, :format, :file_path, :invoice_url,
                    :sent_via, NOW(), :recipient_phone, :recipient_email,
                    :message_sent, :created_by
                )
            ");
            
            $params = [
                ':invoice_number' => $invoiceData['invoice_number'],
                ':client_id' => $invoiceData['client_id'],
                ':service_id' => $serviceId,
                ':invoice_type' => $invoiceType,
                ':total_amount' => $invoiceData['totals']['service_total'],
                ':paid_amount' => $invoiceData['totals']['paid_total'],
                ':pending_amount' => $invoiceData['totals']['pending_total'],
                ':remaining_amount' => $invoiceData['totals']['remaining_total'],
                ':invoice_date' => date('Y-m-d'),
                ':format' => $format,
                ':file_path' => $filePath,
                ':invoice_url' => $invoiceUrl,
                ':sent_via' => $sendVia,
                ':recipient_phone' => $recipientPhone,
                ':recipient_email' => $recipientEmail,
                ':message_sent' => $messageSent,
                ':created_by' => $createdBy
            ];
            
            $stmt->execute($params);
            
            $invoiceId = $this->db->lastInsertId();
            error_log("Invoice history saved successfully. ID: " . $invoiceId . ", Invoice Number: " . $invoiceData['invoice_number']);
            
            return $invoiceId;
        } catch (PDOException $e) {
            error_log("Error saving invoice history: " . $e->getMessage());
            error_log("SQL Error Info: " . print_r($e->errorInfo ?? [], true));
            return false;
        }
    }

    /**
     * Get invoice history with filters
     * @param array $filters Filters (client_id, service_id, date_from, date_to, format, sent_via)
     * @param int $limit Limit of results
     * @param int $offset Offset for pagination
     * @return array List of invoices
     */
    public function getInvoiceHistory($filters = [], $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT i.*, 
                           c.company_name, c.client_number,
                           s.service_name
                    FROM invoices i
                    INNER JOIN clients c ON i.client_id = c.id
                    LEFT JOIN services s ON i.service_id = s.id
                    WHERE 1=1";
            $params = [];
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND i.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND i.service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Date range filter
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND i.invoice_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND i.invoice_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Format filter
            if (isset($filters['format']) && !empty($filters['format'])) {
                $sql .= " AND i.format = :format";
                $params[':format'] = $filters['format'];
            }
            
            // Sent via filter
            if (isset($filters['sent_via']) && !empty($filters['sent_via'])) {
                $sql .= " AND i.sent_via = :sent_via";
                $params[':sent_via'] = $filters['sent_via'];
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'i.sent_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
            
            // Limit and offset
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting invoice history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of invoices matching filters
     * @param array $filters Same filters as getInvoiceHistory
     * @return int Total count
     */
    public function getInvoiceHistoryCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total
                    FROM invoices i
                    WHERE 1=1";
            $params = [];
            
            // Same filters as getInvoiceHistory
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND i.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND i.service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND i.invoice_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND i.invoice_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            if (isset($filters['format']) && !empty($filters['format'])) {
                $sql .= " AND i.format = :format";
                $params[':format'] = $filters['format'];
            }
            
            if (isset($filters['sent_via']) && !empty($filters['sent_via'])) {
                $sql .= " AND i.sent_via = :sent_via";
                $params[':sent_via'] = $filters['sent_via'];
            }
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error getting invoice history count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get invoice by ID
     * @param int $invoiceId Invoice ID
     * @return array|false Invoice data or false if not found
     */
    public function getInvoiceById($invoiceId) {
        try {
            $stmt = $this->db->prepare("
                SELECT i.*, 
                       c.company_name, c.client_number,
                       s.service_name
                FROM invoices i
                INNER JOIN clients c ON i.client_id = c.id
                LEFT JOIN services s ON i.service_id = s.id
                WHERE i.id = :id
            ");
            $stmt->execute([':id' => $invoiceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting invoice by ID: " . $e->getMessage());
            return false;
        }
    }
}

