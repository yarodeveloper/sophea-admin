<?php
require_once 'admin_auth_helper.php';

// Use authentication helper
$auth_data = requireAdminAuth();
$auth = $auth_data['auth'];
$currentUser = $auth_data['user'];

$GLOBALS['admin_page_title'] = 'Detalle del Cliente - Panel de Administración - SOPHEA';

// Include required classes
require_once 'classes/Client.php';
require_once 'classes/Service.php';
require_once 'classes/Quote.php';
require_once 'classes/Payment.php';
require_once 'classes/Expense.php';
require_once 'classes/ProjectTransaction.php';

// Initialize classes early (needed for AJAX requests)
try {
    $client = new Client();
    $service = new Service();
    $quote = new Quote();
    $payment = new Payment();
    $expense = new Expense();
    $projectTransaction = new ProjectTransaction();
} catch (Exception $e) {
    error_log("Error initializing classes: " . $e->getMessage());
    $client = null;
    $service = null;
    $quote = null;
    $payment = null;
    $expense = null;
    $projectTransaction = null;
}

// Handle AJAX request for service payments (BEFORE client ID check)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_service_payments' && isset($_GET['service_id'])) {
    header('Content-Type: application/json');
    
    $serviceId = intval($_GET['service_id']);
    $paymentsData = [];
    $summary = ['total_paid' => 0, 'total_pending' => 0, 'total' => 0];
    
    if ($payment && $serviceId > 0 && $service) {
        try {
            // Get service name
            $serviceData = $service->getServiceById($serviceId);
            $serviceName = $serviceData ? ($serviceData['service_name'] ?? 'Servicio') : 'Servicio';
            
            // Get payments for this service
            $servicePayments = $payment->getPaymentsByService($serviceId);
            
            // Calculate summary
            foreach ($servicePayments as $pay) {
                $summary['total'] += $pay['amount'];
                if ($pay['status'] === 'paid') {
                    $summary['total_paid'] += $pay['amount'];
                } else {
                    $summary['total_pending'] += $pay['amount'];
                }
            }
            
            $paymentsData = [
                'success' => true,
                'payments' => $servicePayments,
                'summary' => $summary,
                'service_name' => $serviceName
            ];
        } catch (Exception $e) {
            error_log("Error fetching service payments: " . $e->getMessage());
            $paymentsData = [
                'success' => false,
                'message' => 'Error al cargar los pagos: ' . $e->getMessage()
            ];
        }
    } else {
        $paymentsData = [
            'success' => false,
            'message' => 'ID de servicio inválido o clases no inicializadas'
        ];
    }
    
    echo json_encode($paymentsData);
    exit;
}

// Handle AJAX request for service investment total
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_service_investment' && isset($_GET['service_id'])) {
    header('Content-Type: application/json');
    
    $serviceId = intval($_GET['service_id']);
    $investmentData = [];
    
    if ($projectTransaction && $serviceId > 0) {
        try {
            // Get balance using the method
            $balance = $projectTransaction->getCustodyBalance($serviceId);
            $totalInvestment = floatval($balance['total_investment'] ?? 0);
            
            $investmentData = [
                'success' => true,
                'investment' => $totalInvestment
            ];
        } catch (Exception $e) {
            error_log("Error fetching service investment: " . $e->getMessage());
            $investmentData = [
                'success' => false,
                'message' => 'Error al cargar la inversión: ' . $e->getMessage()
            ];
        }
    } else {
        $investmentData = [
            'success' => false,
            'message' => 'ID de servicio inválido o clases no inicializadas'
        ];
    }
    
    echo json_encode($investmentData);
    exit;
}

// Handle AJAX request for service costs
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_service_costs' && isset($_GET['service_id'])) {
    header('Content-Type: application/json');
    
    $serviceId = intval($_GET['service_id']);
    $costsData = [];
    
    if ($projectTransaction && $serviceId > 0) {
        try {
            // Get additional investments (income_ads transactions) for this service
            // Excluir las que vienen de pagos (tienen payment_id), solo mostrar inversiones adicionales manuales
            $allIncomeAds = $projectTransaction->getTransactionsByService($serviceId, [
                'transaction_type' => 'income_ads',
                'order_by' => 'transaction_date',
                'order_dir' => 'DESC'
            ]);
            
            // Filtrar solo las inversiones adicionales (sin payment_id, o con payment_id null)
            $costs = array_filter($allIncomeAds, function($tx) {
                return empty($tx['payment_id']) || $tx['payment_id'] == null;
            });
            
            $costsData = [
                'success' => true,
                'costs' => $costs
            ];
        } catch (Exception $e) {
            error_log("Error fetching service costs: " . $e->getMessage());
            $costsData = [
                'success' => false,
                'message' => 'Error al cargar las inversiones: ' . $e->getMessage()
            ];
        }
    } else {
        $costsData = [
            'success' => false,
            'message' => 'ID de servicio inválido o clases no inicializadas'
        ];
    }
    
    echo json_encode($costsData);
    exit;
}

// Handle AJAX request for all transactions
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_all_transactions' && isset($_GET['service_id'])) {
    header('Content-Type: application/json');
    
    $serviceId = intval($_GET['service_id']);
    $transactionsData = [];
    
    if ($projectTransaction && $serviceId > 0) {
        try {
            // Get all transactions for this service
            $allTransactions = $projectTransaction->getTransactionsByService($serviceId, [
                'order_by' => 'transaction_date',
                'order_dir' => 'DESC'
            ]);
            
            // Calculate summary
            $summary = [
                'total_fees' => 0,
                'total_investment' => 0
            ];
            
            // Solo contar income_fee e income_ads (no se registran consumos)
            foreach ($allTransactions as $tx) {
                if ($tx['transaction_type'] === 'income_fee') {
                    $summary['total_fees'] += floatval($tx['amount']);
                } elseif ($tx['transaction_type'] === 'income_ads') {
                    $summary['total_investment'] += floatval($tx['amount']);
                }
                // expense_ads_consumed ya no se usa - no se registran consumos
            }
            
            // Get custody balance
            $balance = $projectTransaction->getCustodyBalance($serviceId);
            
            $transactionsData = [
                'success' => true,
                'transactions' => $allTransactions,
                'summary' => $summary,
                'balance' => $balance
            ];
        } catch (Exception $e) {
            error_log("Error fetching all transactions: " . $e->getMessage());
            $transactionsData = [
                'success' => false,
                'message' => 'Error al cargar las transacciones: ' . $e->getMessage()
            ];
        }
    } else {
        $transactionsData = [
            'success' => false,
            'message' => 'ID de servicio inválido o clases no inicializadas'
        ];
    }
    
    echo json_encode($transactionsData);
    exit;
}

// Handle AJAX request to update service progress
if (isset($_GET['ajax']) && $_GET['ajax'] === 'update_progress' && isset($_POST['service_id']) && isset($_POST['progress'])) {
    header('Content-Type: application/json');
    
    $serviceId = intval($_POST['service_id']);
    $progress = intval($_POST['progress']);
    $progress = max(0, min(100, $progress)); // Clamp between 0 and 100
    
    $updateData = [];
    
    if ($service && $serviceId > 0) {
        try {
            $result = $service->updateProgress($serviceId, $progress);
            
            // Check if service should be finished (100% progress and fully paid)
            $newStatus = 'active';
            if ($result && $progress == 100) {
                // Check payment status
                $svcData = $service->getServiceById($serviceId);
                // We need payment summary, but getServiceById doesn't give it. 
                // Let's use getServicesWithPaymentSummary logic or similar if available, or just fetch payments
                if ($svcData) {
                    $hasPending = false;
                    $totalPaid = 0;
                    $monthlyFee = floatval($svcData['monthly_fee']);
                    $initialInvestment = floatval($svcData['initial_investment_amount'] ?? 0);
                    $expectedTotal = $monthlyFee + $initialInvestment;
                    
                    if ($payment) {
                        $payments = $payment->getPaymentsByService($serviceId);
                        foreach($payments as $p) {
                            if ($p['status'] == 'paid') $totalPaid += floatval($p['amount']);
                            if ($p['status'] == 'pending' || $p['status'] == 'overdue') $hasPending = true;
                        }
                    }
                    
                    // Solo se pasa a historial (completed) si:
                    // 1. El progreso es 100%
                    // 2. No hay pagos pendientes ni vencidos
                    // 3. El total pagado es >= al monto esperado (Honorarios + Inversión Ads si aplica)
                    if ($progress == 100 && !$hasPending && $totalPaid >= $expectedTotal) {
                        // Update status to 'completed'
                        $db = Database::getInstance()->getConnection();
                        $finishedStmt = $db->prepare("UPDATE services SET status = 'completed' WHERE id = ?");
                        $finishedStmt->execute([$serviceId]);
                        $newStatus = 'completed';

                        // Logic for Recurring Services
                        if (isset($svcData['is_recurring']) && $svcData['is_recurring'] == 1) {
                            // Encontrar si ya se creó el siguiente periodo
                            $nextPeriodSql = "SELECT id FROM services WHERE base_service_id = ? AND period_number = ?";
                            $nextPeriodStmt = $db->prepare($nextPeriodSql);
                            $baseId = $svcData['base_service_id'] ?? $svcData['id'];
                            $nextNum = ($svcData['period_number'] ?? 1) + 1;
                            $nextPeriodStmt->execute([$baseId, $nextNum]);
                            $alreadyExists = $nextPeriodStmt->fetch();

                            if (!$alreadyExists) {
                                // Renew service
                                $service->renewService($serviceId);
                            }
                        }
                    }
                }
            }
            
            if ($result) {
                $updateData = [
                    'success' => true,
                    'message' => 'Progreso actualizado exitosamente',
                    'progress' => $progress,
                    'new_status' => $newStatus
                ];
            } else {
                $updateData = [
                    'success' => false,
                    'message' => 'Error al actualizar el progreso'
                ];
            }
        } catch (Exception $e) {
            error_log("Error updating service progress: " . $e->getMessage());
            $updateData = [
                'success' => false,
                'message' => 'Error al actualizar el progreso: ' . $e->getMessage()
            ];
        }
    } else {
        $updateData = [
            'success' => false,
            'message' => 'ID de servicio inválido o servicio no inicializado'
        ];
    }
    
    echo json_encode($updateData);
    exit;
}

// Handle AJAX request to get client services
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_client_services' && isset($_GET['client_id'])) {
    header('Content-Type: application/json');
    
    $clientId = intval($_GET['client_id']);
    $servicesData = [];
    
    if ($service && $clientId > 0) {
        try {
            $services = $service->getServicesByClient($clientId, null);
            $servicesData = [
                'success' => true,
                'services' => array_map(function($s) {
                    return [
                        'id' => $s['id'],
                        'service_name' => $s['service_name'] ?? 'Servicio',
                        'status' => $s['status'] ?? 'active'
                    ];
                }, $services)
            ];
        } catch (Exception $e) {
            error_log("Error fetching client services: " . $e->getMessage());
            $servicesData = [
                'success' => false,
                'message' => 'Error al cargar los servicios: ' . $e->getMessage()
            ];
        }
    } else {
        $servicesData = [
            'success' => false,
            'message' => 'ID de cliente inválido o servicio no inicializado'
        ];
    }
    
    echo json_encode($servicesData);
    exit;
}

// Get client ID (only needed for non-AJAX requests)
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    header('Location: admin_clients.php');
    exit;
}

$pageTitle = 'Detalle de Cliente - Panel de Administración - SOPHEA';

// Handle payment creation from client detail
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Handle quote to service conversion
    if ($_POST['action'] === 'convert_quote_to_service') {
        $quoteId = isset($_POST['quote_id']) ? intval($_POST['quote_id']) : 0;
        
        if ($quoteId > 0 && $quote && $service && $payment) {
            try {
                // Get quote data
                $quoteData = $quote->getQuoteById($quoteId);
                
                if (!$quoteData) {
                    $message = 'Cotización no encontrada';
                    $messageType = 'error';
                } elseif ($quoteData['client_id'] != $clientId) {
                    $message = 'La cotización no pertenece a este cliente';
                    $messageType = 'error';
                } else {
                    // Check if service already exists for this quote
                    $existingService = $service->getServiceByQuoteId($quoteId);
                    
                    if ($existingService) {
                        $message = 'Ya existe un servicio creado desde esta cotización: ' . htmlspecialchars($existingService['service_name']);
                        $messageType = 'error';
                    } else {
                        // Get quote items
                        $quoteItems = $quote->getQuoteItems($quoteId);
                        
                        if (empty($quoteItems)) {
                            $message = 'La cotización no tiene items para convertir';
                            $messageType = 'error';
                        } else {
                            // Group items by service_type
                            $servicesByType = [];
                            require_once 'classes/AppConstants.php';
                            $serviceTypeLabels = AppConstants::getServiceTypes();
                            
                            foreach ($quoteItems as $item) {
                                $serviceType = $item['service_type'] ?? 'otro';
                                if (!isset($servicesByType[$serviceType])) {
                                    $servicesByType[$serviceType] = [
                                        'items' => [],
                                        'total' => 0,
                                        'descriptions' => []
                                    ];
                                }
                                $servicesByType[$serviceType]['items'][] = $item;
                                $servicesByType[$serviceType]['total'] += floatval($item['total'] ?? 0);
                                if (!empty($item['description'])) {
                                    $servicesByType[$serviceType]['descriptions'][] = $item['description'];
                                }
                            }
                            
                            // Create services for each service type
                            $createdServices = [];
                            $paymentsCreated = 0;
                            
                            foreach ($servicesByType as $serviceType => $serviceData) {
                                $serviceName = ($serviceTypeLabels[$serviceType] ?? ucfirst($serviceType)) . ' - ' . $quoteData['title'];
                                $serviceDescription = implode("\n", array_unique($serviceData['descriptions']));
                                if (empty($serviceDescription)) {
                                    $serviceDescription = $quoteData['description'] ?? null;
                                }
                                
                                // Determine if it's an ads service
                                $isAdsService = in_array($serviceType, ['ads', 'ads_facebook', 'ads_google', 'ads_instagram', 'ads_tiktok', 'ads_linkedin', 'ads_other']);
                                
                                // --- NUEVO: Usar item_type explícito (sin heurística de palabras clave) ---
                                $totalFee = 0;
                                $totalInvestment = 0;
                                
                                foreach ($serviceData['items'] as $item) {
                                    $itemType = $item['item_type'] ?? 'fee'; // Default 'fee' si es cotización antigua
                                    $itemTotal = floatval($item['total'] ?? 0);
                                    
                                    if ($isAdsService && $itemType === 'ads_investment') {
                                        $totalInvestment += $itemTotal;
                                    } else {
                                        $totalFee += $itemTotal;
                                    }
                                }
                                // Si es servicio ADS pero no se desglosó (cotización antigua), todo va como fee
                                // para no generar deuda de inversión automática sin confirmación del usuario.
                                
                                $serviceDataToCreate = [
                                    'client_id' => $quoteData['client_id'],
                                    'quote_id' => $quoteId,
                                    'service_type' => $serviceType,
                                    'service_name' => $serviceName,
                                    'description' => $serviceDescription,
                                    'project_description' => $quoteData['description'] ?? null,
                                    'monthly_fee' => $totalFee,
                                    'setup_fee' => 0.00,
                                    'billing_cycle' => 'monthly',
                                    'start_date' => date('Y-m-d'),
                                    'end_date' => null,
                                    'renewal_date' => date('Y-m-d', strtotime('+1 month')),
                                    'progress_percentage' => 0,
                                    'status' => 'active',
                                    'is_ads_service' => $isAdsService,
                                    'initial_investment_amount' => $totalInvestment,
                                    'created_by' => $currentUser['id']
                                ];
                                
                                $serviceId = $service->createService($serviceDataToCreate);
                                
                                if ($serviceId) {
                                    $createdServices[] = $serviceId;
                                    
                                    // Generar pago pendiente si el usuario lo solicitó
                                    if (isset($_POST['create_pending_payment']) && $_POST['create_pending_payment'] == '1') {
                                        $paymentData = [
                                            'client_id' => $quoteData['client_id'],
                                            'service_id' => $serviceId,
                                            'quote_id' => $quoteId,
                                            'currency' => $quoteData['currency'] ?? 'MXN',
                                            'payment_method' => 'transfer',
                                            'payment_date' => date('Y-m-d'),
                                            'due_date' => date('Y-m-d', strtotime('+7 days')),
                                            'status' => 'pending',
                                            'notes' => 'Generado automáticamente al convertir cotización ' . $quoteData['quote_number'],
                                            'created_by' => $currentUser['id']
                                        ];
                                        
                                        if ($isAdsService) {
                                            $includeInvestment = isset($_POST['payment_includes_investment']) && $_POST['payment_includes_investment'] == '1';
                                            $paymentData['fee_amount'] = $totalFee;
                                            $paymentData['ads_amount'] = $includeInvestment ? $totalInvestment : 0;
                                            $paymentData['amount'] = $paymentData['fee_amount'] + $paymentData['ads_amount'];
                                        } else {
                                            $paymentData['amount'] = $totalFee;
                                        }
                                        
                                        if ($paymentData['amount'] > 0) {
                                            $payment->createPayment($paymentData);
                                            $paymentsCreated++;
                                        }
                                    } else {
                                        $paymentsCreated++; // Contamos el servicio creado como "procesado"
                                    }
                                }
                            }
                            
                            if (empty($createdServices)) {
                                $message = 'Error al crear los servicios desde la cotización';
                                $messageType = 'error';
                            } else {
                                $servicesCount = count($createdServices);
                                $servicesText = $servicesCount === 1 ? 'servicio' : 'servicios';
                                $message = "Conversión exitosa: {$servicesCount} {$servicesText} creado(s).";
                                $messageType = 'success';
                                
                                // Redirect to refresh the page and show new services
                                header('Location: admin_client_detail.php?id=' . $clientId . '&message=' . urlencode($message) . '&messageType=' . $messageType);
                                exit;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error converting quote to service: " . $e->getMessage());
                $message = 'Error al convertir la cotización: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Datos inválidos para convertir la cotización';
            $messageType = 'error';
        }
    }
    elseif ($_POST['action'] === 'create_payment_from_service') {
        if ($payment) {
            $paymentData = [
                'client_id' => intval($_POST['client_id']),
                'service_id' => !empty($_POST['service_id']) ? intval($_POST['service_id']) : null,
                'amount' => floatval($_POST['amount']),
                'fee_amount' => !empty($_POST['fee_amount']) ? floatval($_POST['fee_amount']) : 0,
                'ads_amount' => !empty($_POST['ads_amount']) ? floatval($_POST['ads_amount']) : 0,
                'currency' => $_POST['currency'] ?? 'MXN',
                'payment_method' => $_POST['payment_method'] ?? 'transfer',
                'payment_date' => $_POST['payment_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status' => $_POST['status'] ?? 'pending',
                'paid_at' => ($_POST['status'] === 'paid' && !empty($_POST['paid_at'])) ? $_POST['paid_at'] : null,
                'reference_number' => !empty($_POST['reference_number']) ? $_POST['reference_number'] : null,
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'created_by' => $currentUser['id']
            ];
            
            // If fee_amount and ads_amount are provided but amount is 0, sum them
            if ($paymentData['amount'] == 0 && ($paymentData['fee_amount'] > 0 || $paymentData['ads_amount'] > 0)) {
                $paymentData['amount'] = $paymentData['fee_amount'] + $paymentData['ads_amount'];
            }
            
            $paymentId = $payment->createPayment($paymentData);
            
            if ($paymentId) {
                // Check if service is now completed (100% progress and fully paid)
                if (!empty($_POST['service_id'])) {
                    $svcId = intval($_POST['service_id']);
                    try {
                        $svcData = $service->getServiceById($svcId);
                        if ($svcData && intval($svcData['progress_percentage']) == 100) {
                            $monthlyFee = floatval($svcData['monthly_fee']);
                            $initialInvestment = floatval($svcData['initial_investment_amount'] ?? 0);
                            $expectedTotal = $monthlyFee + $initialInvestment;

                            // Check total paid and pending status
                            $payments = $payment->getPaymentsByService($svcId);
                            $totalPaid = 0;
                            $hasPending = false;

                            foreach($payments as $p) {
                                // Consider the current payment as paid if it was just created/updated as paid
                                $pStatus = $p['status'];
                                if ($p['id'] == $paymentId && $_POST['status'] == 'paid') {
                                    $pStatus = 'paid';
                                }

                                if ($pStatus == 'paid') {
                                    $totalPaid += floatval($p['amount']);
                                } else if ($pStatus == 'pending' || $pStatus == 'overdue') {
                                    $hasPending = true;
                                }
                            }
                            
                            // Solo se pasa a historial (completed) si:
                            // 1. El progreso es 100%
                            // 2. No hay pagos pendientes ni vencidos
                            // 3. El total pagado es >= al monto esperado (Honorarios + Inversión Ads si aplica)
                            if (!$hasPending && $totalPaid >= $expectedTotal) {
                                // Update status to completed
                                $db = Database::getInstance()->getConnection();
                                $finishedStmt = $db->prepare("UPDATE services SET status = 'completed' WHERE id = ?");
                                $finishedStmt->execute([$svcId]);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error checking completion status: " . $e->getMessage());
                    }
                }

                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Pago registrado exitosamente');
                exit;
            } else {
                $message = 'Error al registrar el pago';
                $messageType = 'error';
            }
        }
    }
    // Handle service deletion
    elseif ($_POST['action'] === 'delete_service') {
        $serviceId = intval($_POST['service_id']);
        if ($service && $serviceId > 0) {
            // Verify no payments (double check)
            $payments = $payment->getPaymentsByService($serviceId);
            $hasPayments = false;
            foreach ($payments as $p) {
                if ($p['status'] === 'paid' || $p['amount'] > 0) { // Any registered payment prevents deletion preferably
                    $hasPayments = true; 
                    break;
                }
            }
            
            if (!$hasPayments) {
                if ($service->deleteService($serviceId)) {
                    header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Servicio eliminado exitosamente');
                    exit;
                } else {
                    $message = 'Error al eliminar el servicio';
                    $messageType = 'error';
                }
            } else {
                $message = 'No se puede eliminar el servicio porque tiene pagos registrados';
                $messageType = 'error';
            }
        }
    }
    // Handle service update
    elseif ($_POST['action'] === 'update_service') {
        $serviceId = intval($_POST['service_id']);
        if ($service && $serviceId > 0) {
            $updateData = [
                'service_type' => $_POST['service_type'],
                'service_name' => $_POST['service_name'],
                'description' => !empty($_POST['description']) ? $_POST['description'] : null,
                'project_description' => !empty($_POST['project_description']) ? $_POST['project_description'] : null,
                'monthly_fee' => floatval($_POST['monthly_fee'] ?? 0),
                'setup_fee' => floatval($_POST['setup_fee'] ?? 0),
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'start_date' => $_POST['start_date'],
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'renewal_date' => !empty($_POST['renewal_date']) ? $_POST['renewal_date'] : null,
                'progress_percentage' => intval($_POST['progress_percentage'] ?? 0),
                'status' => $_POST['status'] ?? 'active',
                'project_url' => !empty($_POST['project_url']) ? $_POST['project_url'] : null,
                'legal_coverage' => !empty($_POST['legal_coverage']) ? $_POST['legal_coverage'] : null,
                'is_ads_service' => isset($_POST['is_ads_service']) ? 1 : 0,
                'initial_investment_amount' => floatval($_POST['initial_investment_amount'] ?? 0),
                'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
                'renewal_mode' => $_POST['renewal_mode'] ?? 'manual',
                'base_service_id' => !empty($_POST['base_service_id']) ? intval($_POST['base_service_id']) : null,
                'period_number' => !empty($_POST['period_number']) ? intval($_POST['period_number']) : 1
            ];
            
            if ($service->updateService($serviceId, $updateData)) {
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Servicio actualizado exitosamente');
                exit;
            } else {
                $message = 'Error al actualizar el servicio';
                $messageType = 'error';
            }
        }
    }
    // Handle permanent client deletion
    elseif ($_POST['action'] === 'permanent_delete_client') {
        if ($client && $clientId > 0) {
            if ($client->deleteClientPermanently($clientId)) {
                // Since fixed client_id is deleted, redirect to client list
                header('Location: admin_clients.php?message=success&msg=Cliente eliminado permanentemente');
                exit;
            } else {
                $message = 'Error al eliminar permanentemente al cliente';
                $messageType = 'error';
            }
        }
    }
    // Handle client update
    elseif ($_POST['action'] === 'update_client') {
        if ($client) {
            $updateData = [
                'company_name' => $_POST['company_name'],
                'contact_name' => $_POST['contact_name'],
                'email' => $_POST['email'],
                'phone' => !empty($_POST['phone']) ? $_POST['phone'] : null,
                'phone_country_code' => $_POST['phone_country_code'] ?? '+52',
                'whatsapp' => !empty($_POST['whatsapp']) ? $_POST['whatsapp'] : null,
                'whatsapp_country_code' => $_POST['whatsapp_country_code'] ?? '+52',
                'address' => !empty($_POST['address']) ? $_POST['address'] : null,
                'city' => !empty($_POST['city']) ? $_POST['city'] : null,
                'state' => !empty($_POST['state']) ? $_POST['state'] : null,
                'country' => 'México', // Default
                'tax_id' => !empty($_POST['tax_id']) ? $_POST['tax_id'] : null,
                'website' => !empty($_POST['website']) ? $_POST['website'] : null,
                'industry' => !empty($_POST['industry']) ? $_POST['industry'] : null,
                'client_type' => $_POST['client_type'] ?? 'regular',
                'legal_risk' => $_POST['legal_risk'] ?? 'low',
                'legal_compliance' => $_POST['legal_compliance'] ?? 100,
                'last_audit_date' => !empty($_POST['last_audit_date']) ? $_POST['last_audit_date'] : null,
                'status' => $_POST['status'] ?? 'active',
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'logo_url' => !empty($_POST['logo_url']) ? $_POST['logo_url'] : null
            ];
            
            if ($client->updateClient($clientId, $updateData)) {
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Datos del cliente actualizados exitosamente');
                exit;
            } else {
                $message = 'Error al actualizar los datos del cliente';
                $messageType = 'error';
            }
        }
    }
    // AJAX handler for deleting a specific document
    elseif ($_POST['action'] === 'delete_client_document') {
        $docIdRaw = $_POST['doc_id'] ?? '';
        $success = false;
        $errorMsg = 'Error desconocido al eliminar';
        
        if ($docIdRaw === 'legacy') {
            if (!empty($clientData['contract_path'])) {
                if (file_exists($clientData['contract_path'])) {
                    @unlink($clientData['contract_path']);
                }
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE clients SET contract_path = NULL, contract_note = NULL WHERE id = ?");
                if ($stmt->execute([$clientId])) {
                    $success = true;
                } else {
                    $errorMsg = 'Error al actualizar registro legado en BD';
                }
            } else {
                $errorMsg = 'No hay archivo legado que eliminar';
            }
        } else {
            $docId = intval($docIdRaw);
            if ($docId > 0) {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT file_path FROM client_documents WHERE id = ?");
                $stmt->execute([$docId]);
                $doc = $stmt->fetch();
                
                if ($doc) {
                    if (file_exists($doc['file_path'])) {
                        @unlink($doc['file_path']);
                    }
                    $delStmt = $db->prepare("DELETE FROM client_documents WHERE id = ?");
                    if ($delStmt->execute([$docId])) {
                        $success = true;
                    } else {
                        $errorMsg = 'Error al eliminar registro de la base de datos';
                    }
                } else {
                    $errorMsg = 'Documento no encontrado en la base de datos';
                }
            } else {
                $errorMsg = 'ID de documento inválido';
            }
        }

        if ($success) {
            header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Documento eliminado correctamente');
            exit;
        } else {
            $message = $errorMsg;
            $messageType = 'error';
        }
    }
    // Handle document upload to the new table (Multi-upload support)
    elseif ($_POST['action'] === 'upload_client_document') {
        if (isset($_FILES['client_doc'])) {
            $files = $_FILES['client_doc'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 0;
            if ($fileCount === 0 && !empty($files['name'])) $fileCount = 1;
            
            $successCount = 0;
            $uploadErrors = [];
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO client_documents (client_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
            
            $uploadDir = 'uploads/documents/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("CRITICAL: No se pudo crear el directorio " . $uploadDir);
                    $uploadErrors[] = "No se pudo crear el directorio de subida.";
                }
            }
            
            if (empty($uploadErrors)) {
                for ($i = 0; $i < $fileCount; $i++) {
                    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    
                    if ($error === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $newFileName = 'doc_' . $clientId . '_' . time() . '_' . uniqid() . '.' . $ext;
                        $dest = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($tmpName, $dest)) {
                            if ($stmt->execute([$clientId, $name, $dest, $ext, $size])) {
                                $successCount++;
                            } else {
                                @unlink($dest);
                                $uploadErrors[] = "Error BD al registrar: " . $name;
                            }
                        } else {
                            $uploadErrors[] = "Fallo al mover el archivo: " . $name;
                        }
                    } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                        $uploadErrors[] = "Error PHP (" . $error . ") en: " . $name;
                    }
                }
            }
            
            if ($successCount > 0) {
                $msg = $successCount . ' archivo(s) subido(s) correctamente';
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=' . urlencode($msg));
                exit;
            } else {
                $message = !empty($uploadErrors) ? implode("<br>", $uploadErrors) : 'No se seleccionaron archivos válidos';
                $messageType = 'error';
            }
        }
    }
    // Handle multiple document note update
    elseif ($_POST['action'] === 'update_client_document_note') {
        $docId = intval($_POST['doc_id']);
        $note = $_POST['note'] ?? '';
        if ($docId > 0) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE client_documents SET note = ? WHERE id = ?");
            if ($stmt->execute([$note, $docId])) {
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Nota actualizada correctamente');
                exit;
            }
        }
    }
    // Nota: Las inversiones adicionales ahora se manejan a través de api_add_investment.php
    elseif ($_POST['action'] === 'delete_service_cost') {
        $message = 'Funcionalidad en desarrollo';
        $messageType = 'info';
    }
    // Handle quote status update
    elseif ($_POST['action'] === 'update_quote_status') {
        $quoteId = isset($_POST['quote_id']) ? intval($_POST['quote_id']) : 0;
        $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
        
        if ($quoteId > 0 && !empty($newStatus) && $quote) {
            if ($quote->updateStatus($quoteId, $newStatus)) {
                $statusLabel = $newStatus === 'accepted' ? 'aceptada' : $newStatus;
                header('Location: admin_client_detail.php?id=' . $clientId . '&message=success&msg=Cotización marcada como ' . $statusLabel);
                exit;
            } else {
                $message = 'Error al actualizar el estado de la cotización';
                $messageType = 'error';
            }
        }
    }
}

// Get messages from URL
if (isset($_GET['message']) && isset($_GET['messageType'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['messageType'];
} elseif (isset($_GET['message']) && $_GET['message'] === 'success') {
    $message = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Operación realizada exitosamente';
    $messageType = 'success';
}

// Get client data
$clientData = null;
$clientSummary = null;
$activeServices = [];
$activeServicesWithPayments = [];
$allQuotes = [];
$pendingPayments = [];
$allPayments = [];
$totalPending = 0;
$totalExpectedFromServices = 0;
$totalPendingPayments = 0;
$activeServicesCount = 0;
$pendingQuotesCount = 0;
$pendingQuotesValue = 0;
$finishedProjectsCount = 0;
$finishedProjectsValue = 0;

if ($client) {
    try {
        $clientData = $client->getClientById($clientId);
        
        if (!$clientData) {
            header('Location: admin_clients.php');
            exit;
        }
        
        // Get client summary
        $clientSummary = $client->getClientSummary($clientId);
        
        // Get active services with payment summary
        if ($service) {
            $activeServices = $service->getServicesByClient($clientId, 'active');
            $activeServicesWithPayments = $service->getServicesWithPaymentSummary($clientId);
            $activeServicesCount = count($activeServices);
            
            // Also fetch finished services
            $finishedServices = $service->getServicesByClient($clientId, 'completed');
            $finishedServicesWithPayments = [];
            
            if (!empty($finishedServices)) {
                foreach ($finishedServices as $svc) {
                    // Check if service/projects is finished
                    // Get payments
                    $svc['payments'] = [];
                    if ($payment) {
                        $svc['payments'] = $payment->getPaymentsByService($svc['id']);
                    }
                    
                    // Calculate totals
                    $totalPaid = 0;
                    foreach ($svc['payments'] as $p) {
                        if ($p['status'] === 'paid') {
                            $totalPaid += floatval($p['amount']);
                        }
                    }
                    
                    $svc['total_paid'] = $totalPaid;

                    // Get Ads investment for finished services
                    $svc['total_ads_investment'] = 0;
                    $isAdsService = !empty($svc['is_ads_service']) && (
                        $svc['is_ads_service'] == 1 || 
                        $svc['is_ads_service'] === true || 
                        $svc['is_ads_service'] === '1' ||
                        intval($svc['is_ads_service']) === 1
                    );
                    
                    if ($isAdsService && $projectTransaction) {
                        try {
                            $balance = $projectTransaction->getCustodyBalance($svc['id']);
                            $svc['total_ads_investment'] = floatval($balance['total_investment'] ?? 0);
                        } catch (Exception $e) {
                            error_log("Error getting custody balance for finished service {$svc['id']}: " . $e->getMessage());
                            $svc['total_ads_investment'] = 0;
                        }
                    }

                    $finishedServicesWithPayments[] = $svc;
                }
            }
            
            // Get Ads investment totals for each service
            if ($projectTransaction && !empty($activeServicesWithPayments)) {
                foreach ($activeServicesWithPayments as &$svc) {
                    // Check if service is Ads service - simplified check
                    $isAdsService = !empty($svc['is_ads_service']) && (
                        $svc['is_ads_service'] == 1 || 
                        $svc['is_ads_service'] === true || 
                        $svc['is_ads_service'] === '1' ||
                        intval($svc['is_ads_service']) === 1
                    );
                    
                    if ($isAdsService) {
                        try {
                            $balance = $projectTransaction->getCustodyBalance($svc['id']);
                            $svc['total_ads_investment'] = floatval($balance['total_investment'] ?? 0);
                        } catch (Exception $e) {
                            error_log("Error getting custody balance for service {$svc['id']}: " . $e->getMessage());
                            $svc['total_ads_investment'] = 0;
                        }
                    } else {
                        $svc['total_ads_investment'] = 0;
                    }
                }
                unset($svc); // Break reference
            }
            
            // Calculate total expected from active services
            $totalExpectedFromServices = $service->getTotalExpectedFromServices($clientId);
            
            // Calculate average progress/compliance from active services
            $averageProgress = 0;
            if (!empty($activeServicesWithPayments)) {
                $totalProgress = 0;
                $servicesWithProgress = 0;
                foreach ($activeServicesWithPayments as $svc) {
                    $progress = intval($svc['progress_percentage'] ?? 0);
                    $totalProgress += $progress;
                    $servicesWithProgress++;
                }
                if ($servicesWithProgress > 0) {
                    $averageProgress = round($totalProgress / $servicesWithProgress, 1);
                }
            } else {
                // If no active services, use the stored legal_compliance value or default to 100
                $averageProgress = floatval($clientData['legal_compliance'] ?? 100);
            }
        }
        
        if ($quote) {
            $allQuotes = $quote->getAllQuotes(['client_id' => $clientId, 'limit' => 20, 'order_by' => 'q.created_at', 'order_dir' => 'DESC']);
            
            // Calculate pending quotes summary and separate them
            $pendingQuotes = [];
            $otherQuotes = [];
            $pendingQuotesCount = 0;
            $pendingQuotesValue = 0;

            if (!empty($allQuotes)) {
                foreach ($allQuotes as $q) {
                    $qStatus = $q['status'] ?? 'draft';
                    if (in_array($qStatus, ['draft', 'sent', 'accepted'])) {
                        // Check if service already exists for this quote
                        $existingSvc = $service ? $service->getServiceByQuoteId($q['id']) : false;
                        if (!$existingSvc) {
                            // Check if the quote has ads items
                            $qItems = $quote->getQuoteItems($q['id']);
                            $q['has_ads'] = false;
                            if (!empty($qItems)) {
                                foreach ($qItems as $item) {
                                    if (in_array($item['service_type'] ?? '', ['ads', 'ads_facebook', 'ads_google', 'ads_instagram', 'ads_tiktok', 'ads_linkedin', 'ads_other'])) {
                                        $q['has_ads'] = true;
                                        break;
                                    }
                                }
                            }
                            $pendingQuotes[] = $q;
                            $pendingQuotesCount++;
                            $pendingQuotesValue += floatval($q['total'] ?? 0);
                        } else {
                            $q['existing_service'] = $existingSvc;
                            $otherQuotes[] = $q;
                        }
                    } else {
                        $otherQuotes[] = $q;
                    }
                }
            }
        }

        // Calculate finished projects summary
        $finishedProjectsCount = count($finishedServicesWithPayments ?? []);
        $finishedProjectsValue = 0;
        if (!empty($finishedServicesWithPayments)) {
            foreach ($finishedServicesWithPayments as $fsvc) {
                $finishedProjectsValue += floatval($fsvc['total_paid'] ?? 0);
            }
        }
        
        // Handle message from URL (after redirect)
        if (isset($_GET['message']) && isset($_GET['messageType'])) {
            $message = urldecode($_GET['message']);
            $messageType = $_GET['messageType'];
        }
        
        // Get payments
        if ($payment) {
            $pendingPayments = $payment->getPendingPayments($clientId);
            $allPayments = $payment->getPaymentsByClient($clientId);
            
            // Total Pendiente = Saldo pendiente (Esperado - Pagado) de servicios activos
            $totalPending = 0;
            if (!empty($activeServicesWithPayments)) {
                foreach ($activeServicesWithPayments as $svc) {
                    $totalPending += max(0, floatval($svc['monthly_fee']) - floatval($svc['total_paid']));
                }
            }
        }
        
        // Get client service costs from project_transactions (expense_ads_consumed)
        $clientCosts = [];
        $totalClientCosts = ['total_cost' => 0, 'total_count' => 0];
        
        if ($projectTransaction && !empty($activeServicesWithPayments)) {
            try {
                $allServiceCosts = [];
                $totalCost = 0;
                
                foreach ($activeServicesWithPayments as $svc) {
                    if (!empty($svc['is_ads_service']) && ($svc['is_ads_service'] == 1 || $svc['is_ads_service'] === true || $svc['is_ads_service'] === '1')) {
                        $costs = $projectTransaction->getTransactionsByService($svc['id'], [
                            'transaction_type' => 'expense_ads_consumed',
                            'order_by' => 'transaction_date',
                            'order_dir' => 'DESC'
                        ]);
                        
                        foreach ($costs as $cost) {
                            $costAmount = abs(floatval($cost['amount']));
                            $totalCost += $costAmount;
                            
                            $allServiceCosts[] = [
                                'id' => $cost['id'],
                                'service_id' => $svc['id'],
                                'service_name' => $svc['service_name'],
                                'amount' => $costAmount,
                                'currency' => $cost['currency'] ?? 'MXN',
                                'platform' => $cost['platform'],
                                'description' => $cost['description'],
                                'transaction_date' => $cost['transaction_date'],
                                'billing_period_start' => $cost['billing_period_start'],
                                'billing_period_end' => $cost['billing_period_end']
                            ];
                        }
                    }
                }
                
                // Sort by date descending
                usort($allServiceCosts, function($a, $b) {
                    return strtotime($b['transaction_date'] ?? '') - strtotime($a['transaction_date'] ?? '');
                });
                
                $clientCosts = array_slice($allServiceCosts, 0, 10);
                $totalClientCosts = [
                    'total_cost' => $totalCost,
                    'total_count' => count($allServiceCosts)
                ];
            } catch (Exception $e) {
                error_log("Error fetching service costs from project_transactions: " . $e->getMessage());
            }
        }
        
        // Get custody balances for Ads services
        $custodyBalances = [];
        if ($projectTransaction) {
            try {
                $custodyBalances = $projectTransaction->getAllCustodyBalances($clientId);
            } catch (Exception $e) {
                error_log("Error fetching custody balances: " . $e->getMessage());
            }
        }
        // Get client documents from new table
        $clientDocs = [];
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM client_documents WHERE client_id = ? ORDER BY created_at DESC");
            $stmt->execute([$clientId]);
            $clientDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add legacy document if exists and not already in the list
            if (!empty($clientData['contract_path'])) {
                $legacyPath = $clientData['contract_path'];
                $alreadyListed = false;
                foreach ($clientDocs as $doc) {
                    if ($doc['file_path'] === $legacyPath) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if (!$alreadyListed) {
                    // Add as a virtual document at the end
                    $clientDocs[] = [
                        'id' => 'legacy',
                        'file_name' => basename($legacyPath),
                        'file_path' => $legacyPath,
                        'note' => $clientData['contract_note'] ?? 'Archivo del contrato original',
                        'created_at' => $clientData['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching client documents: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Error fetching client data: " . $e->getMessage());
    }
}

// Include header
include 'includes/admin_header.php';
?>

<!-- Sidebar removed for layout -->
<style>
    /* Custom Dropdown for Invoice Actions */
    .invoice-dropdown {
        position: relative;
        display: inline-block;
    }
    .invoice-dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #ffffff;
        min-width: 180px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 100;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .dark .invoice-dropdown-content {
        background-color: #1e293b;
        border-color: #334155;
    }
    .invoice-dropdown:hover .invoice-dropdown-content,
    .invoice-dropdown.active .invoice-dropdown-content {
        display: block;
    }
    /* Bridge to prevent flicker if there's a tiny gap */
    .invoice-dropdown::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        height: 10px;
        display: none;
    }
    .invoice-dropdown:hover::after {
        display: block;
    }
    .invoice-dropdown-content a, .invoice-dropdown-content button {
        color: #1e293b;
        padding: 10px 14px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        text-align: left;
        font-size: 13px;
        transition: background 0.2s;
    }
    .dark .invoice-dropdown-content a, .dark .invoice-dropdown-content button {
        color: #f1f5f9;
        text-align: left;
    }
    .invoice-dropdown-content a:hover, .invoice-dropdown-content button:hover {
        background-color: #f1f5f9;
    }
    .dark .invoice-dropdown-content a:hover, .dark .invoice-dropdown-content button:hover {
        background-color: #334155;
    }
</style>

<?php include 'includes/layout_start.php'; ?>
            <!-- Back Button -->
            <div class="mb-4">
                <a href="admin_clients.php" 
                   class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span>Volver a Clientes</span>
                </a>
            </div>

            <!-- Client Header -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($clientData['company_name'] ?? 'Cliente'); ?>
                            </h2>
                            <?php
                            $typeLabels = [
                                'prospect' => 'Prospecto',
                                'regular' => 'Cliente Regular',
                                'strategic_partner' => 'Socio Estratégico'
                            ];
                            $typeColors = [
                                'prospect' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'regular' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                'strategic_partner' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                            ];
                            $clientType = $clientData['client_type'] ?? 'regular';
                            ?>
                            <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo $typeColors[$clientType] ?? $typeColors['regular']; ?>">
                                <?php echo $typeLabels[$clientType] ?? 'Cliente'; ?>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">tag</span>
                                <span><?php echo htmlspecialchars($clientData['client_number'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if ($clientData['legal_risk'] ?? null): ?>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">shield</span>
                                    <span>Riesgo Legal: <?php echo ucfirst($clientData['legal_risk']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="openQuickPaymentModal()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">payments</span>
                            Registrar Pago
                        </button>
                        <a href="admin_quotes.php?new_client_id=<?php echo $clientId; ?>" 
                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">description</span>
                            Nueva Cotización
                        </a>
                        <button onclick="openInvoiceModal()" 
                                class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">receipt</span>
                            Enviar Factura
                        </button>
                        <a href="admin_services.php?action=new&client_id=<?php echo $clientId; ?>" 
                           class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition font-medium flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Añadir Servicio
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'; ?>">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-2xl mr-3"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                        <div>
                            <p class="font-semibold"><?php echo $messageType === 'success' ? 'Éxito' : 'Error'; ?></p>
                            <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
                <!-- Total Pendiente -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 text-orange-600 dark:text-orange-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Saldo Pendiente</span>
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                    <div class="text-2xl font-bold">
                        $<?php echo number_format($totalPending, 2); ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Saldo total por cobrar (activo)
                    </div>
                </div>

                <!-- Servicios Activos -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Servicios Activos</span>
                        <span class="material-symbols-outlined text-2xl text-primary">rocket_launch</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo $activeServicesCount; ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        En curso actualmente
                    </div>
                </div>

                <!-- Cotizaciones Pendientes (Interactive Card) -->
                <div class="col-span-1 md:col-span-2 xl:col-span-1 bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col overflow-hidden">
                    <?php if (empty($pendingQuotes)): ?>
                        <!-- Standard style when empty to match other cards -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Cotizaciones</span>
                                <span class="material-symbols-outlined text-2xl text-primary">description</span>
                            </div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">
                                0
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Sin cotizaciones pendientes
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Interactive style when there are quotes -->
                        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-card-dark">
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Cotizaciones</span>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-bold rounded-full">
                                    <?php echo $pendingQuotesCount; ?> PENDIENTES
                                </span>
                                <span class="material-symbols-outlined text-2xl text-primary">description</span>
                            </div>
                        </div>
                        <div class="p-4 flex-1 overflow-y-auto max-h-[300px] space-y-3 custom-scrollbar">
                            <?php foreach (array_slice($pendingQuotes, 0, 3) as $q): ?>
                                <?php 
                                $quoteStatus = $q['status'] ?? 'draft';
                                $quoteStatusLabels = ['draft' => 'Borrador', 'sent' => 'Enviada', 'accepted' => 'Aceptada'];
                                $quoteStatusColors = [
                                    'draft' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
                                    'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200',
                                    'accepted' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200'
                                ];
                                ?>
                                <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/20 hover:border-primary/30 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($q['title']); ?></h4>
                                            <p class="text-[10px] text-slate-500 font-mono mt-0.5"><?php echo htmlspecialchars($q['quote_number']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs font-black text-slate-900 dark:text-white">$<?php echo number_format($q['total'], 2); ?></div>
                                            <span class="inline-block mt-1 px-1.5 py-0.5 text-[9px] font-bold rounded uppercase <?php echo $quoteStatusColors[$quoteStatus]; ?>">
                                                <?php echo $quoteStatusLabels[$quoteStatus]; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-1 mt-3">
                                        <?php if ($quoteStatus === 'sent'): ?>
                                            <form method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>" class="flex-1">
                                                <input type="hidden" name="action" value="update_quote_status">
                                                <input type="hidden" name="quote_id" value="<?php echo $q['id']; ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-[9px] font-bold py-1.5 rounded transition-all flex items-center justify-center gap-1 shadow-sm">
                                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                    ACEPTAR
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($quoteStatus, ['sent', 'accepted'])): ?>
                                            <button type="button" 
                                                    onclick="openConvertQuoteModal(<?php echo $q['id']; ?>, '<?php echo htmlspecialchars($q['quote_number']); ?>', <?php echo ($q['has_ads'] ?? false) ? 'true' : 'false'; ?>)"
                                                    class="w-full bg-primary hover:bg-primary/90 text-white text-[9px] font-bold py-1.5 rounded transition-all flex items-center justify-center gap-1 shadow-sm">
                                                <span class="material-symbols-outlined text-[14px]">rocket_launch</span>
                                                CONVERTIR
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="generate_quote.php?quote_id=<?php echo $q['id']; ?>&client_id=<?php echo $clientId; ?>&preview=1" 
                                           target="_blank" class="px-2 py-1.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded hover:bg-slate-300 transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 text-center">
                            <a href="admin_quotes.php?client_id=<?php echo $clientId; ?>" class="text-[10px] font-bold text-primary hover:underline flex items-center justify-center gap-1">
                                VER TODAS LAS COTIZACIONES
                                <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Proyectos Concluidos -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Proyectos Concluidos</span>
                        <span class="material-symbols-outlined text-2xl text-primary">task_alt</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        <?php echo $finishedProjectsCount; ?>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Valor: $<?php echo number_format($finishedProjectsValue, 2); ?>
                    </div>
                </div>

                <!-- Avance General -->
                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Avance General</span>
                        <span class="material-symbols-outlined text-2xl text-primary">trending_up</span>
                    </div>
                    <div class="text-2xl font-bold <?php 
                        if ($averageProgress >= 80) echo 'text-green-600 dark:text-green-400';
                        elseif ($averageProgress >= 50) echo 'text-yellow-600 dark:text-yellow-400';
                        else echo 'text-orange-600 dark:text-orange-400';
                    ?>">
                        <?php echo number_format($averageProgress, 1); ?>%
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Cumplimiento de objetivos
                    </div>
                </div>
            </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Resumen Financiero</h3>
                                <div class="flex gap-2">
                                    <button onclick="toggleFinanceView('active')" id="btn-finance-active" 
                                            class="text-xs px-3 py-1 bg-slate-100 dark:bg-slate-700 font-medium rounded transition">
                                        Activos
                                    </button>
                                    <button onclick="toggleFinanceView('finished')" id="btn-finance-finished"
                                            class="text-xs px-3 py-1 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition">
                                        Historial
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto" id="finance-active-table">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Proyecto / Servicio</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tarifa</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagos Recibidos</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pagos Pendientes</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Saldo Pendiente</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <?php if (empty($activeServicesWithPayments)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                                <div class="flex flex-col items-center gap-2">
                                                    <span class="material-symbols-outlined text-4xl">inbox</span>
                                                    <p class="text-lg font-medium">No hay servicios activos</p>
                                                    <p class="text-sm">No se puede mostrar el resumen sin servicios activos</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $grandTotalTarifa = 0;
                                        $grandTotalRecibido = 0;
                                        $grandTotalPendiente = 0;
                                        $grandTotalSaldo = 0;
                                        
                                        foreach ($activeServicesWithPayments as $svc): 
                                            $grandTotalTarifa += $svc['monthly_fee'];
                                            $grandTotalRecibido += $svc['total_paid'];
                                            $grandTotalPendiente += $svc['total_pending'];
                                            $grandTotalSaldo += max(0, $svc['pending_balance']);
                                        ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <?php if ($svc['project_url']): ?>
                                                            <a href="<?php echo htmlspecialchars($svc['project_url']); ?>" 
                                                               target="_blank"
                                                               class="text-primary hover:text-primary/80"
                                                               title="Ver proyecto">
                                                                <span class="material-symbols-outlined text-lg">open_in_new</span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                                                <?php echo htmlspecialchars($svc['service_name'] ?? 'Servicio'); ?>
                                                                <?php if (!empty($svc['is_recurring'])): ?>
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="Servicio Recurrente">
                                                                        <span class="material-symbols-outlined text-[10px] mr-0.5">repeat</span>
                                                                        R<?php echo $svc['period_number'] ?? 1; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($svc['description']): ?>
                                                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                                    <?php echo htmlspecialchars(substr($svc['description'], 0, 50)) . (strlen($svc['description']) > 50 ? '...' : ''); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                                        $<?php echo number_format($svc['monthly_fee'], 2); ?>
                                                    </div>
                                                    <?php 
                                                    // Check if service is Ads service - simplified check
                                                    $isAdsService = !empty($svc['is_ads_service']) && (
                                                        $svc['is_ads_service'] == 1 || 
                                                        $svc['is_ads_service'] === true || 
                                                        $svc['is_ads_service'] === '1' ||
                                                        intval($svc['is_ads_service']) === 1
                                                    );
                                                    
                                                    // Fallback por tipo de servicio
                                                    if (!$isAdsService && isset($svc['service_type'])) {
                                                        $isAdsService = ($svc['service_type'] === 'ads' || strpos($svc['service_type'], 'ads_') === 0);
                                                    }
                                                    
                                                    // Get ads investment amount
                                                    $adsInvestment = isset($svc['total_ads_investment']) ? floatval($svc['total_ads_investment']) : 0;
                                                    
                                                    // If not calculated yet but is Ads service, calculate it now
                                                    if ($isAdsService && $adsInvestment == 0 && $projectTransaction) {
                                                        try {
                                                            $balance = $projectTransaction->getCustodyBalance($svc['id']);
                                                            $adsInvestment = floatval($balance['total_investment'] ?? 0);
                                                        } catch (Exception $e) {
                                                            error_log("Error getting custody balance in view for service {$svc['id']}: " . $e->getMessage());
                                                            $adsInvestment = 0;
                                                        }
                                                    }
                                                    // Show if it's an Ads service and has investment
                                                    if ($isAdsService && $adsInvestment > 0): 
                                                    ?>
                                                        <div class="ads-total-display text-[10px] font-bold text-blue-600 dark:text-blue-400 mt-1 flex items-center gap-1">
                                                            <span class="material-symbols-outlined notranslate text-[12px]">campaign</span> Ads $<?php echo number_format($adsInvestment, 2); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                        $<?php echo number_format($svc['total_paid'], 2); ?>
                                                    </div>
                                                    <?php if ($svc['payments_count'] > 0): ?>
                                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                                            <?php echo $svc['payments_count']; ?> pago(s)
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <div class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                                                        $<?php echo number_format($svc['total_pending'], 2); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <div class="text-sm font-bold <?php echo $svc['pending_balance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-400'; ?>">
                                                        $<?php echo number_format(max(0, $svc['pending_balance']), 2); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <?php 
                                                        $serviceNameJs = htmlspecialchars(json_encode($svc['service_name'] ?? 'Servicio'), ENT_QUOTES, 'UTF-8');
                                                        $monthlyFeeJs = floatval($svc['monthly_fee'] ?? 0);
                                                        $pendingBalanceJs = floatval($svc['pending_balance'] ?? 0);
                                                        ?>
                                                        <button onclick='openPaymentModal(<?php echo $svc['id']; ?>, <?php echo $serviceNameJs; ?>, <?php echo $monthlyFeeJs; ?>, <?php echo $pendingBalanceJs; ?>, <?php echo $isAdsService ? 1 : 0; ?>)' 
                                                                class="text-primary hover:text-primary/80" title="Registrar Pago">
                                                            <span class="material-symbols-outlined text-lg">payments</span>
                                                        </button>
                                                        <button onclick='viewServicePayments(<?php echo $svc['id']; ?>, <?php echo $serviceNameJs; ?>)' 
                                                                class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200" title="Ver Pagos">
                                                            <span class="material-symbols-outlined text-lg">receipt_long</span>
                                                        </button>
                                                        <button onclick='openEditServiceModal(<?php echo json_encode($svc); ?>)' 
                                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Editar Servicio">
                                                            <span class="material-symbols-outlined text-lg">edit</span>
                                                        </button>
                                                        <?php 
                                                        // Verificar si es servicio Ads - usar la misma lógica que para mostrar la inversión
                                                        $isAdsServiceForIcon = !empty($svc['is_ads_service']) && (
                                                            $svc['is_ads_service'] == 1 || 
                                                            $svc['is_ads_service'] === true || 
                                                            $svc['is_ads_service'] === '1' ||
                                                            intval($svc['is_ads_service']) === 1
                                                        );
                                                        
                                                        // Fallback por tipo de servicio o nombre
                                                        $serviceType = $svc['service_type'] ?? '';
                                                        $isAdsType = ($serviceType === 'ads' || strpos($serviceType, 'ads_') === 0);
                                                        
                                                        if ($isAdsServiceForIcon || $isAdsType): 
                                                        ?>
                                                        <button onclick='openServiceCostsModal(<?php echo $svc['id']; ?>, <?php echo $clientId; ?>, <?php echo $serviceNameJs; ?>)' 
                                                                class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300" title="Inversiones Adicionales">
                                                            <span class="material-symbols-outlined text-lg">receipt</span>
                                                        </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php 
                                                        // Botón de eliminar (SOLO si no tiene pagos)
                                                        if ($svc['total_paid'] <= 0): 
                                                            $serviceNameSafe = str_replace("'", "\'", $svc['service_name'] ?? 'este servicio');
                                                        ?>
                                                        <button onclick="confirmDeleteService(<?php echo $svc['id']; ?>, '<?php echo $serviceNameSafe; ?>')" 
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Eliminar Servicio">
                                                            <span class="material-symbols-outlined text-lg">delete</span>
                                                        </button>
                                                        <?php endif; ?>
                                                         
                                                        <!-- Factura Proyecto Dropdown -->
                                                        <div class="invoice-dropdown">
                                                            <button type="button" onclick="event.stopPropagation(); this.parentElement.classList.toggle('active')" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors" title="Factura / Recibo">
                                                                <span class="material-symbols-outlined text-lg">receipt</span>
                                                            </button>
                                                            <div class="invoice-dropdown-content">
                                                                <a href="generate_invoice.php?client_id=<?php echo $clientId; ?>&service_id=<?php echo $svc['id']; ?>&format=pdf" target="_blank">
                                                                    <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                                                                    Descargar PDF
                                                                </a>
                                                                <button type="button" onclick="sendPaymentWhatsApp(null, <?php echo $clientId; ?>, <?php echo $svc['id']; ?>, 'whatsapp')">
                                                                    <span class="material-symbols-outlined text-sm">send</span>
                                                                    Enviar a WhatsApp
                                                                </button>
                                                                <button type="button" onclick="sendPaymentWhatsApp(null, <?php echo $clientId; ?>, <?php echo $svc['id']; ?>, 'email')">
                                                                    <span class="material-symbols-outlined text-sm">mail</span>
                                                                    Enviar por Correo
                                                                </button>
                                                                <button type="button" onclick="sendPaymentWhatsApp(null, <?php echo $clientId; ?>, <?php echo $svc['id']; ?>, 'both')">
                                                                    <span class="material-symbols-outlined text-sm">checklist</span>
                                                                    Enviar por Ambos
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <!-- Totals Row -->
                                        <tr class="bg-slate-50 dark:bg-slate-800/50 font-semibold">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                                    TOTALES
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                                    $<?php echo number_format($grandTotalTarifa, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                                    $<?php echo number_format($grandTotalRecibido, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-bold text-yellow-600 dark:text-yellow-400">
                                                    $<?php echo number_format($grandTotalPendiente, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                                    $<?php echo number_format($grandTotalSaldo, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4"></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Finished Finance Table (Hidden by default) -->
                        <div class="overflow-x-auto hidden" id="finance-finished-table">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Proyecto Terminado</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Periodo</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Total Cobrado</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Inversión Ads</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <?php if (empty($finishedServicesWithPayments)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                                No hay historial financiero registrado
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($finishedServicesWithPayments as $fsvc): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($fsvc['service_name']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 text-right text-xs text-slate-500 dark:text-slate-400">
                                                    <?php 
                                                    $fstart = !empty($fsvc['start_date']) ? date('d/m/Y', strtotime($fsvc['start_date'])) : 'N/A';
                                                    $fend = !empty($fsvc['end_date']) ? date('d/m/Y', strtotime($fsvc['end_date'])) : 'Completado';
                                                    echo $fstart . ' - ' . $fend; 
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 text-right font-semibold text-green-600 dark:text-green-400">
                                                    $<?php echo number_format($fsvc['total_paid'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 text-right font-semibold text-blue-600 dark:text-blue-400">
                                                    $<?php echo number_format($fsvc['total_ads_investment'] ?? 0, 2); ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <button onclick='viewServicePayments(<?php echo $fsvc['id']; ?>, <?php echo htmlspecialchars(json_encode($fsvc['service_name']), ENT_QUOTES, 'UTF-8'); ?>)' 
                                                            class="text-slate-500 hover:text-primary transition-colors">
                                                        <span class="material-symbols-outlined text-lg">receipt_long</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Servicios</h3>
                                <div class="flex gap-2">
                                    <button onclick="toggleServicesView('active')" id="btn-active-services" 
                                            class="text-sm px-3 py-1 bg-slate-100 dark:bg-slate-700 font-medium rounded transition">
                                        Activos
                                    </button>
                                    <button onclick="toggleServicesView('finished')" id="btn-finished-services"
                                            class="text-sm px-3 py-1 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition">
                                        Historial
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Services List -->
                        <div id="active-services-list" class="p-6 space-y-4">
                            <?php if (empty($activeServicesWithPayments)): ?>
                                <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">inbox</span>
                                    <p>No hay servicios activos</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeServicesWithPayments as $svc): ?>
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition relative">
                                        <!-- Update Progress Button - Top Right Corner -->
                                        <?php
                                        $progressPercentage = intval($svc['progress_percentage'] ?? 0);
                                        $isProject = !empty($svc['end_date']);
                                        ?>
                                        <?php 
                                        $serviceNameJs2 = htmlspecialchars(json_encode($svc['service_name'] ?? 'Servicio'), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button onclick='openUpdateProgressModal(<?php echo $svc['id']; ?>, <?php echo $serviceNameJs2; ?>, <?php echo $progressPercentage; ?>, <?php echo $isProject ? 'true' : 'false'; ?>)' 
                                                class="absolute top-3 right-3 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600 px-2 py-1 rounded text-xs font-medium transition flex items-center gap-1"
                                                title="Actualizar Progreso">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                            <span><?php echo $progressPercentage; ?>%</span>
                                        </button>
                                        
                                        <!-- Service Name and Description -->
                                        <div class="pr-20 mb-2">
                                            <div class="flex items-start gap-2 mb-1">
                                                <h4 class="font-semibold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                                                    <?php echo htmlspecialchars($svc['service_name'] ?? 'Servicio'); ?>
                                                    <?php if (!empty($svc['is_recurring'])): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="Servicio Recurrente">
                                                            <span class="material-symbols-outlined text-[10px] mr-0.5">repeat</span>
                                                            R<?php echo $svc['period_number'] ?? 1; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </h4>
                                                <?php if ($svc['project_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($svc['project_url']); ?>" 
                                                       target="_blank"
                                                       class="text-primary hover:text-primary/80 flex-shrink-0"
                                                       title="Ver proyecto">
                                                        <span class="material-symbols-outlined text-base">open_in_new</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($svc['description']): ?>
                                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                                    <?php echo htmlspecialchars($svc['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Project Description / Observations -->
                                        <?php if ($svc['project_description']): ?>
                                            <div class="mb-2">
                                                <p class="text-xs text-slate-500 dark:text-slate-400 italic">
                                                    <?php echo htmlspecialchars($svc['project_description']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Progress Tracking Section -->
                                        <?php
                                        // Determine if it's a project (has end_date) or recurring service
                                        $isProject = !empty($svc['end_date']);
                                        $isRecurring = !$isProject && in_array($svc['billing_cycle'] ?? 'monthly', ['monthly', 'quarterly', 'yearly']);
                                        
                                        $progressPercentage = intval($svc['progress_percentage'] ?? 0);
                                        $startDate = $svc['start_date'] ? new DateTime($svc['start_date']) : null;
                                        $endDate = $svc['end_date'] ? new DateTime($svc['end_date']) : null;
                                        $now = new DateTime();
                                        
                                        // Calculate time progress for projects
                                        $timeProgress = null;
                                        $daysRemaining = null;
                                        $isOverdue = false;
                                        
                                        if ($isProject && $startDate && $endDate) {
                                            $totalDays = $startDate->diff($endDate)->days;
                                            $elapsedDays = $startDate->diff($now)->days;
                                            $timeProgress = $totalDays > 0 ? min(100, ($elapsedDays / $totalDays) * 100) : 0;
                                            $daysRemaining = $endDate->diff($now)->days;
                                            $isOverdue = $now > $endDate && $progressPercentage < 100;
                                        }
                                        ?>
                                        
                                        <div class="mt-2 space-y-2">
                                            <!-- Progress Bar -->
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Progreso</span>
                                                        <?php if ($isProject): ?>
                                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                                Proyecto
                                                            </span>
                                                        <?php elseif ($isRecurring): ?>
                                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                                Recurrente
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-xs font-bold text-slate-900 dark:text-white"><?php echo $progressPercentage; ?>%</span>
                                                </div>
                                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                                                         style="width: <?php echo min(100, max(0, $progressPercentage)); ?>%"></div>
                                                </div>
                                                
                                                <!-- Project Timeline (only for projects with end_date) -->
                                                <?php if ($isProject && $startDate && $endDate): ?>
                                                    <div class="mt-2 p-2 bg-slate-50 dark:bg-slate-800/30 rounded text-xs">
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div>
                                                                <span class="text-slate-500 dark:text-slate-400">Inicio:</span>
                                                                <span class="font-medium text-slate-900 dark:text-white ml-1">
                                                                    <?php echo $startDate->format('d/m/Y'); ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <span class="text-slate-500 dark:text-slate-400">Fin:</span>
                                                                <span class="font-medium <?php echo $isOverdue ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'; ?> ml-1">
                                                                    <?php echo $endDate->format('d/m/Y'); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($timeProgress !== null && $daysRemaining !== null): ?>
                                                        <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-slate-500 dark:text-slate-400">Tiempo:</span>
                                                                <span class="text-xs font-medium <?php echo $timeProgress > $progressPercentage ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-600 dark:text-slate-400'; ?>">
                                                                    <?php echo number_format($timeProgress, 1); ?>%
                                                                </span>
                                                                <span class="text-xs <?php echo $daysRemaining < 0 ? 'text-red-600 dark:text-red-400' : ($daysRemaining <= 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-slate-500 dark:text-slate-400'); ?>">
                                                                    <?php if ($daysRemaining < 0): ?>
                                                                        <?php echo abs($daysRemaining); ?>d retraso
                                                                    <?php elseif ($daysRemaining == 0): ?>
                                                                        Vence hoy
                                                                    <?php else: ?>
                                                                        <?php echo $daysRemaining; ?>d restantes
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($isRecurring): ?>
                                                    <!-- Recurring Service Info -->
                                                    <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-xs">
                                                        <span class="text-green-700 dark:text-green-400">
                                                            <?php 
                                                            $cycleLabels = [
                                                                'monthly' => 'Mensual',
                                                                'quarterly' => 'Trimestral',
                                                                'yearly' => 'Anual'
                                                            ];
                                                            echo $cycleLabels[$svc['billing_cycle'] ?? 'monthly'] ?? 'Recurrente';
                                                            ?>
                                                            <?php if ($svc['renewal_date']): ?>
                                                                | Renovación: <?php echo (new DateTime($svc['renewal_date']))->format('d/m/Y'); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($svc['legal_coverage']): ?>
                                            <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-green-600 dark:text-green-400">verified</span>
                                                    <div>
                                                        <p class="text-sm font-medium text-green-800 dark:text-green-300">Cobertura de Riesgo Legal</p>
                                                        <p class="text-xs text-green-700 dark:text-green-400"><?php echo htmlspecialchars($svc['legal_coverage']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Finished Services List (Hidden by default) -->
                        <div id="finished-services-list" class="hidden p-6 space-y-4">
                            <?php if (empty($finishedServicesWithPayments)): ?>
                                <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                                    <p>No hay proyectos completados</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($finishedServicesWithPayments as $svc): ?>
                                    <div class="border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 rounded-lg p-4 relative opacity-75 hover:opacity-100 transition">
                                        <div class="absolute top-3 right-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                                Completado
                                            </span>
                                        </div>
                                        
                                        <div class="pr-24 mb-2">
                                            <div class="flex items-start gap-2 mb-1">
                                                <h4 class="font-semibold text-slate-900 dark:text-white text-base flex items-center gap-2">
                                                    <?php echo htmlspecialchars($svc['service_name'] ?? 'Servicio'); ?>
                                                    <?php if (!empty($svc['is_recurring'])): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="Servicio Recurrente">
                                                            <span class="material-symbols-outlined text-[10px] mr-0.5">repeat</span>
                                                            R<?php echo $svc['period_number'] ?? 1; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </h4>
                                                <?php if (!empty($svc['project_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($svc['project_url']); ?>" 
                                                       target="_blank"
                                                       class="text-primary hover:text-primary/80 flex-shrink-0"
                                                       title="Ver proyecto">
                                                        <span class="material-symbols-outlined text-base">open_in_new</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($svc['description']): ?>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                    <?php echo htmlspecialchars($svc['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Project Description / Observations -->
                                        <?php if (!empty($svc['project_description'])): ?>
                                            <div class="mb-2">
                                                <p class="text-xs text-slate-500 dark:text-slate-400 italic">
                                                    <?php echo htmlspecialchars($svc['project_description']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                            <div>
                                                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Periodo</span>
                                                <div class="text-sm text-slate-900 dark:text-white flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm text-slate-400">date_range</span>
                                                    <?php 
                                                    $start = $svc['start_date'] ? date('d/m/Y', strtotime($svc['start_date'])) : 'N/A';
                                                    $end = $svc['end_date'] ? date('d/m/Y', strtotime($svc['end_date'])) : 'Completado';
                                                    echo $start . ' - ' . $end;
                                                    ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Total Cobrado</span>
                                                <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                    $<?php echo number_format($svc['total_paid'], 2); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if (!empty($svc['total_ads_investment']) && $svc['total_ads_investment'] > 0): ?>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Inversión Ads</span>
                                                    <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                                        $<?php echo number_format($svc['total_ads_investment'], 2); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center justify-end">
                                               <?php 
                                                    $serviceNameJs = htmlspecialchars(json_encode($svc['service_name'] ?? 'Servicio'), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <button onclick='viewServicePayments(<?php echo $svc['id']; ?>, <?php echo $serviceNameJs; ?>)' 
                                                        class="text-xs flex items-center gap-1 text-slate-600 hover:text-primary dark:text-slate-400 dark:hover:text-primary transition">
                                                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                                                    Ver Pagos
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3) -->
                <div class="space-y-6">
                    <!-- Client Info -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Información de Contacto</h3>
                            <div class="flex gap-2">
                                <button onclick="openClientEditModal()" 
                                        class="text-sm px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Editar
                                </button>
                                <button onclick="confirmPermanentDeleteClient()" 
                                        class="text-sm px-3 py-1 bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/30 transition flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">delete_forever</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Contacto:</span>
                                <div class="text-slate-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($clientData['contact_name'] ?? '-'); ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Email:</span>
                                <div class="flex items-center gap-2 group">
                                    <div class="text-slate-900 dark:text-white font-medium">
                                        <?php echo htmlspecialchars($clientData['email'] ?? '-'); ?>
                                    </div>
                                    <button id="copy-email" onclick="copyToClipboard('<?php echo addslashes($clientData['email'] ?? ''); ?>', 'copy-email')" 
                                            class="text-slate-400 hover:text-primary transition-colors opacity-0 group-hover:opacity-100" title="Copiar Email">
                                        <span class="material-symbols-outlined text-lg">content_copy</span>
                                    </button>
                                </div>
                            </div>
                            <?php if ($clientData['phone']): ?>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Teléfono:</span>
                                    <div class="flex items-center gap-2 group">
                                        <div class="text-slate-900 dark:text-white font-medium">
                                            <?php echo htmlspecialchars($clientData['phone']); ?>
                                        </div>
                                        <button id="copy-phone" onclick="copyToClipboard('<?php echo addslashes($clientData['phone']); ?>', 'copy-phone')" 
                                                class="text-slate-400 hover:text-primary transition-colors opacity-0 group-hover:opacity-100" title="Copiar Teléfono">
                                            <span class="material-symbols-outlined text-lg">content_copy</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($clientData['whatsapp']): ?>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">WhatsApp:</span>
                                    <div class="flex items-center justify-between group">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-slate-900 dark:text-white font-medium">
                                                    <?php 
                                                    $whatsappNumber = $clientData['whatsapp'];
                                                    $whatsappCountryCode = $clientData['whatsapp_country_code'] ?? '+52';
                                                    $whatsappFull = $whatsappCountryCode . $whatsappNumber;
                                                    $whatsappClean = preg_replace('/[^0-9]/', '', $whatsappFull);
                                                    echo htmlspecialchars($whatsappNumber); 
                                                    ?>
                                                </span>
                                                <button id="copy-whatsapp" onclick="copyToClipboard('<?php echo addslashes($whatsappNumber); ?>', 'copy-whatsapp')" 
                                                        class="text-slate-400 hover:text-primary transition-colors opacity-0 group-hover:opacity-100" title="Copiar WhatsApp">
                                                    <span class="material-symbols-outlined text-lg">content_copy</span>
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-1 ml-2">
                                                <!-- Icono para abrir WhatsApp Web -->
                                                <a href="https://web.whatsapp.com/send?phone=<?php echo $whatsappClean; ?>" 
                                                   target="_blank"
                                                   class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 transition-colors"
                                                   title="Abrir WhatsApp Web">
                                                    <span class="material-symbols-outlined text-lg">open_in_new</span>
                                                </a>
                                                <!-- Icono para enviar mensaje (abre modal) -->
                                                <button onclick="openWhatsAppModal(<?php echo $clientId; ?>, '<?php echo htmlspecialchars(addslashes($clientData['company_name'] ?? 'Cliente')); ?>', '<?php echo htmlspecialchars($whatsappFull); ?>', '<?php echo htmlspecialchars($whatsappClean); ?>')" 
                                                        class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 transition-colors"
                                                        title="Enviar mensaje por WhatsApp">
                                                    <span class="material-symbols-outlined text-lg">send</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($clientData['address']): ?>
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Dirección:</span>
                                    <div class="text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($clientData['address']); ?>
                                        <?php if ($clientData['city']): ?>
                                            <br><?php echo htmlspecialchars($clientData['city']); ?>
                                        <?php endif; ?>
                                        <?php if ($clientData['state']): ?>
                                            , <?php echo htmlspecialchars($clientData['state']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Documentos Adjuntos -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/20">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">folder_open</span>
                                Documentos Adjuntos
                                <span class="bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] px-1.5 py-0.5 rounded-full"><?php echo count($clientDocs); ?></span>
                            </h3>
                            <button onclick="window.document.getElementById('doc-upload-input').click()" 
                                    class="text-[10px] bg-primary/10 text-primary hover:bg-primary/20 px-2 py-1 rounded-md transition-all font-bold uppercase tracking-wider flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">add</span>
                                Subir Nuevo
                            </button>
                        </div>
                        
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[400px] overflow-y-auto custom-scrollbar">
                            <?php if (empty($clientDocs)): ?>
                                <div class="p-6 text-center">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">cloud_off</span>
                                    <p class="text-xs text-slate-500">No hay documentos cargados</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($clientDocs as $doc): ?>
                                    <div class="group p-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <div class="flex gap-4">
                                            <!-- Izquierda: Documento -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="material-symbols-outlined text-slate-400 text-base">
                                                        <?php 
                                                        $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                                                        if (in_array($ext, ['pdf'])) echo 'picture_as_pdf';
                                                        elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) echo 'image';
                                                        elseif (in_array($ext, ['doc', 'docx'])) echo 'description';
                                                        else echo 'draft_orders';
                                                        ?>
                                                    </span>
                                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate" title="<?php echo htmlspecialchars($doc['file_name']); ?>">
                                                        <?php echo htmlspecialchars($doc['file_name']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[9px] text-slate-500"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></span>
                                                    <div class="flex items-center gap-1">
                                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-[10px] text-primary hover:underline font-bold">VER</a>
                                                        <span class="text-slate-300">|</span>
                                                        <button onclick="openEditNoteModal('<?php echo $doc['id']; ?>', '<?php echo addslashes($doc['note'] ?? ''); ?>')" class="text-[10px] text-slate-500 hover:text-primary font-bold">NOTA</button>
                                                        <span class="text-slate-300">|</span>
                                                        <button onclick="if(confirm('¿Eliminar este documento?')) { deleteDocument('<?php echo $doc['id']; ?>') }" class="text-[10px] text-red-400 hover:text-red-600 font-bold">BORRAR</button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Derecha: Nota -->
                                            <div class="flex-1 border-l border-slate-100 dark:border-slate-800 pl-4 min-w-0">
                                                <p class="text-[10px] text-slate-500 italic line-clamp-2 leading-relaxed" title="<?php echo htmlspecialchars($doc['note'] ?? 'Sin nota'); ?>">
                                                    <?php echo !empty($doc['note']) ? htmlspecialchars($doc['note']) : '<span class="text-slate-300">Sin nota adicional...</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Hidden Form for Upload -->
                        <form id="upload-doc-form" method="POST" enctype="multipart/form-data" style="display:none;">
                            <input type="hidden" name="action" value="upload_client_document">
                            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                            <input type="file" id="doc-upload-input" name="client_doc[]" multiple onchange="this.form.submit()">
                        </form>
                        
                        <!-- Hidden Form for Delete -->
                        <form id="delete-doc-form" method="POST" style="display:none;">
                            <input type="hidden" name="action" value="delete_client_document">
                            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                            <input type="hidden" name="doc_id" id="delete-doc-id">
                        </form>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Pagos Recientes</h3>
                        <div class="space-y-3">
                            <?php if (empty($allPayments)): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">No hay pagos registrados</p>
                            <?php else: ?>
                                <?php foreach (array_slice($allPayments, 0, 5) as $p): ?>
                                    <div class="border-b border-slate-200 dark:border-slate-700 pb-3 last:border-0 last:pb-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($p['service_name'] ?? 'Pago general'); ?>
                                            </span>
                                            <span class="text-xs font-semibold <?php echo $p['status'] === 'paid' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'; ?>">
                                                $<?php echo number_format($p['amount'], 2); ?> <?php echo htmlspecialchars($p['currency'] ?? 'MXN'); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                <?php echo !empty($p['payment_date']) ? date('d/m/Y', strtotime($p['payment_date'])) : 'N/A'; ?>
                                            </span>
                                            <?php
                                            $statusColors = [
                                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                            ];
                                            require_once 'classes/AppConstants.php';
                                            $statusLabels = AppConstants::getPaymentStatuses();

                                            $status = $p['status'] ?? 'pending';
                                            ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $statusColors[$status] ?? $statusColors['pending']; ?>">
                                                <?php echo $statusLabels[$status] ?? ucfirst($status); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="admin_payments.php?client_id=<?php echo $clientId; ?>" 
                           class="mt-4 block text-sm text-primary hover:text-primary/80 text-center">
                            Ver todos los pagos
                        </a>
                    </div>

                    <!-- Inversión en Ads (Solo inversiones, no consumos) -->
                    <?php if (!empty($custodyBalances)): 
                        $totalInvestment = 0;
                        foreach ($custodyBalances as $cb) {
                            $totalInvestment += floatval($cb['total_investment'] ?? 0);
                        }
                    ?>
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Inversión en Ads</h3>
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                Total: $<?php echo number_format($totalInvestment, 2); ?>
                            </span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($custodyBalances as $cb): 
                                $investment = floatval($cb['total_investment'] ?? 0);
                                if ($investment > 0):
                            ?>
                                <div class="border-b border-slate-200 dark:border-slate-700 pb-3 last:border-0 last:pb-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                            <?php echo htmlspecialchars($cb['service_name'] ?? 'Servicio'); ?>
                                        </span>
                                        <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                            $<?php echo number_format($investment, 2); ?> MXN
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        Inversión publicitaria acumulada
                                    </p>
                                </div>
                            <?php 
                                endif;
                            endforeach; ?>
                            <?php if ($totalInvestment == 0): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">No hay inversiones registradas</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Other Quotes (History Summary) -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Historial de Cotizaciones</h3>
                        <div class="space-y-3">
                            <?php if (empty($otherQuotes)): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">No hay otras cotizaciones registradas</p>
                            <?php else: ?>
                                <?php foreach (array_slice($otherQuotes, 0, 5) as $q): ?>
                                    <div class="border-b border-slate-200 dark:border-slate-700 pb-3 last:border-0 last:pb-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($q['title'] ?? 'Cotización'); ?>
                                            </span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                $<?php echo number_format($q['total'], 2); ?>
                                            </span>
                                        </div>
                                        <?php if (isset($q['existing_service'])): ?>
                                            <div class="text-[10px] text-amber-600 dark:text-amber-400 flex items-center gap-1 mb-1">
                                                <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                                Convertido: <?php echo htmlspecialchars($q['existing_service']['service_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                                    <?php echo htmlspecialchars($q['quote_number'] ?? ''); ?>
                                                </span>
                                                <a href="generate_quote.php?quote_id=<?php echo $q['id']; ?>&client_id=<?php echo $clientId; ?>&preview=1" 
                                                   target="_blank" class="text-slate-400 hover:text-primary transition-colors" title="Ver Cotización">
                                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                                </a>
                                            </div>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase <?php 
                                                $qStatus = $q['status'] ?? 'draft';
                                                $qColors = [
                                                    'draft' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
                                                    'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200',
                                                    'accepted' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200',
                                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200',
                                                    'expired' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200'
                                                ];
                                                echo $qColors[$qStatus] ?? $qColors['draft']; 
                                            ?>">
                                                <?php 
                                                $qLabels = ['draft' => 'Borrador', 'sent' => 'Enviada', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada', 'expired' => 'Vencida'];
                                                echo $qLabels[$qStatus] ?? ucfirst($qStatus); 
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="admin_quotes.php?client_id=<?php echo $clientId; ?>" class="mt-4 block text-xs text-primary font-bold hover:underline text-center">
                            VER HISTORIAL COMPLETO
                        </a>
                    </div>

                    <!-- Notes -->
                    <?php if ($clientData['notes']): ?>
                        <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Notas</h3>
                            <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                <?php echo nl2br(htmlspecialchars($clientData['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


<!-- Client Edit Modal -->
<div id="clientEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-card-dark z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Editar Cliente</h3>
                <button onclick="closeClientEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>" class="p-6">
            <input type="hidden" name="action" value="update_client">
            
            <!-- Hidden fields for values we might not want to edit but must preserve -->
            <input type="hidden" name="last_audit_date" value="<?php echo htmlspecialchars($clientData['last_audit_date'] ?? ''); ?>">
            <input type="hidden" name="logo_url" value="<?php echo htmlspecialchars($clientData['logo_url'] ?? ''); ?>">
            <input type="hidden" name="legal_compliance" value="<?php echo htmlspecialchars($clientData['legal_compliance'] ?? 100); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- General Info -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Información General</h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre de la Empresa *</label>
                    <input type="text" name="company_name" required value="<?php echo htmlspecialchars($clientData['company_name']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sitio Web</label>
                    <input type="url" name="website" value="<?php echo htmlspecialchars($clientData['website'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">RFC / Tax ID</label>
                    <input type="text" name="tax_id" value="<?php echo htmlspecialchars($clientData['tax_id'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Industria / Sector</label>
                    <input type="text" name="industry" value="<?php echo htmlspecialchars($clientData['industry'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Contact Info -->
                <div class="md:col-span-2 mt-2">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Contacto</h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre de Contacto *</label>
                    <input type="text" name="contact_name" required value="<?php echo htmlspecialchars($clientData['contact_name']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Email *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($clientData['email']); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-1">
                         <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cód. País</label>
                         <select name="phone_country_code" class="w-full px-2 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="+52" <?php echo ($clientData['phone_country_code'] ?? '+52') === '+52' ? 'selected' : ''; ?>>MX (+52)</option>
                            <option value="+1" <?php echo ($clientData['phone_country_code'] ?? '') === '+1' ? 'selected' : ''; ?>>US (+1)</option>
                            <option value="+34" <?php echo ($clientData['phone_country_code'] ?? '') === '+34' ? 'selected' : ''; ?>>ES (+34)</option>
                            <option value="+54" <?php echo ($clientData['phone_country_code'] ?? '') === '+54' ? 'selected' : ''; ?>>AR (+54)</option>
                            <option value="+57" <?php echo ($clientData['phone_country_code'] ?? '') === '+57' ? 'selected' : ''; ?>>CO (+57)</option>
                         </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Teléfono</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($clientData['phone'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-1">
                         <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cód. WhatsApp</label>
                         <select name="whatsapp_country_code" class="w-full px-2 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white">
                            <option value="+52" <?php echo ($clientData['whatsapp_country_code'] ?? '+52') === '+52' ? 'selected' : ''; ?>>MX (+52)</option>
                            <option value="+1" <?php echo ($clientData['whatsapp_country_code'] ?? '') === '+1' ? 'selected' : ''; ?>>US (+1)</option>
                            <option value="+34" <?php echo ($clientData['whatsapp_country_code'] ?? '') === '+34' ? 'selected' : ''; ?>>ES (+34)</option>
                            <option value="+54" <?php echo ($clientData['whatsapp_country_code'] ?? '') === '+54' ? 'selected' : ''; ?>>AR (+54)</option>
                            <option value="+57" <?php echo ($clientData['whatsapp_country_code'] ?? '') === '+57' ? 'selected' : ''; ?>>CO (+57)</option>
                         </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">WhatsApp</label>
                        <input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($clientData['whatsapp'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <!-- Address -->
                <div class="md:col-span-2 mt-2">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Dirección</h4>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Calle y Número</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($clientData['address'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ciudad</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($clientData['city'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($clientData['state'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Classification -->
                <div class="md:col-span-2 mt-2">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Clasificación</h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de Cliente</label>
                    <select name="client_type" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="prospect" <?php echo ($clientData['client_type'] ?? '') === 'prospect' ? 'selected' : ''; ?>>Prospecto</option>
                        <option value="regular" <?php echo ($clientData['client_type'] ?? '') === 'regular' ? 'selected' : ''; ?>>Cliente Regular</option>
                        <option value="strategic_partner" <?php echo ($clientData['client_type'] ?? '') === 'strategic_partner' ? 'selected' : ''; ?>>Socio Estratégico</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <select name="status" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="active" <?php echo ($clientData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo ($clientData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="prospect" <?php echo ($clientData['status'] ?? '') === 'prospect' ? 'selected' : ''; ?>>Prospecto</option>
                        <option value="archived" <?php echo ($clientData['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Riesgo Legal</label>
                    <select name="legal_risk" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="low" <?php echo ($clientData['legal_risk'] ?? '') === 'low' ? 'selected' : ''; ?>>Bajo</option>
                        <option value="medium" <?php echo ($clientData['legal_risk'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medio</option>
                        <option value="high" <?php echo ($clientData['legal_risk'] ?? '') === 'high' ? 'selected' : ''; ?>>Alto</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notas</label>
                    <textarea name="notes" rows="4" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($clientData['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6 border-t border-slate-200 dark:border-slate-700 pt-4">
                <button type="button" onclick="closeClientEditModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Service Costs Modal -->
<div id="serviceCostsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="serviceCostsModalTitle">Inversiones Adicionales - Campañas de Ads</h3>
                <button onclick="closeServiceCostsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Form to Add New Cost - SIMPLIFICADO -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-800/30 rounded-lg border border-slate-200 dark:border-slate-700">
                <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Registrar Inversión Adicional</h4>
                <form id="serviceCostForm">
                    <input type="hidden" name="client_id" id="costClientId" value="<?php echo $clientId; ?>">
                    <input type="hidden" name="service_id" id="costServiceId" value="">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Monto de Inversión *
                            </label>
                            <input type="number" name="amount" id="costAmount" required step="0.01" min="0.01"
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Plataforma
                            </label>
                            <select name="platform" id="costPlatform"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="meta" selected>META</option>
                                <option value="whatsapp">WhatsApp META</option>
                                <option value="google">Google</option>
                                <option value="tiktok">TikTok</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="other">Otra</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Período *
                            </label>
                            <input type="date" name="period_date" id="costPeriodDate" required
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Descripción
                            </label>
                            <textarea name="description" id="costDescription" rows="2"
                                      placeholder="Ej: Inversión adicional para campaña Meta Ads"
                                      class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" onclick="closeServiceCostsModal()" 
                                class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                            Registrar Inversión
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- List of Costs -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-slate-900 dark:text-white">Historial de Inversiones</h4>
                    <button onclick="viewAllTransactions()" 
                            class="text-sm px-3 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/30 transition">
                        Ver Todas las Transacciones
                    </button>
                </div>
                <div id="serviceCostsList" class="space-y-3">
                    <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Selecciona un servicio para ver sus costos</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Transactions Modal -->
<div id="allTransactionsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-card-dark z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="allTransactionsModalTitle">Historial Completo de Transacciones</h3>
                <button onclick="closeAllTransactionsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" id="transactionsSummary">
                <!-- Summary will be loaded here -->
            </div>
            
            <!-- Transactions List -->
            <div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Transacciones</h4>
                <div id="allTransactionsList" class="space-y-3">
                    <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Cargando transacciones...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Payments Modal -->
<div id="servicePaymentsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-card-dark z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="servicePaymentsModalTitle">Pagos del Servicio</h3>
                <button onclick="closeServicePaymentsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <div class="p-6" id="servicePaymentsContainer">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editServiceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-card-dark z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Editar Servicio</h3>
                <button onclick="closeEditServiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>" class="p-6">
            <input type="hidden" name="action" value="update_service">
            <input type="hidden" name="service_id" id="editServiceId">
            <input type="hidden" name="base_service_id" id="editBaseServiceId">
            <input type="hidden" name="period_number" id="editPeriodNumber">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre del Servicio *</label>
                    <input type="text" name="service_name" id="editServiceName" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de Servicio *</label>
                    <select name="service_type" id="editServiceType" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php 
                        require_once 'classes/AppConstants.php';
                        $serviceTypes = AppConstants::getServiceTypes();
                        foreach ($serviceTypes as $key => $label): 
                        ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <select name="status" id="editServiceStatus"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="active">Activo</option>
                        <option value="completed">Completado</option>
                        <option value="cancelled">Cancelado</option>
                        <option value="paused">Pausado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Honorarios Mensuales (Fee)</label>
                    <input type="number" name="monthly_fee" id="editMonthlyFee" step="0.01"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Costo de Setup (Único)</label>
                    <input type="number" name="setup_fee" id="editSetupFee" step="0.01"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="md:col-span-2 flex items-center gap-2 py-2">
                    <input type="checkbox" name="is_ads_service" id="editIsAdsService" value="1" class="rounded border-slate-300 text-primary focus:ring-primary">
                    <label class="text-sm font-bold text-purple-600 dark:text-purple-400">Este es un servicio de Publicidad (Ads)</label>
                </div>

                <div id="editAdsFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 hidden p-4 bg-purple-50 dark:bg-purple-900/10 rounded-lg border border-purple-100 dark:border-purple-800/30">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Inversión Ads Inicial (Pauta)</label>
                        <input type="number" name="initial_investment_amount" id="editInitialInvestmentAmount" step="0.01"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div class="md:col-span-1 border-l border-purple-200 dark:border-purple-800/50 pl-6 flex flex-col justify-center">
                        <p class="text-xs text-purple-700 dark:text-purple-300">
                            <strong>Nota:</strong> Al marcar como Ads, podrás registrar desgloses entre Fee e Inversión.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ciclo de Facturación</label>
                    <select name="billing_cycle" id="editBillingCycle"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="monthly">Mensual</option>
                        <option value="quarterly">Trimestral</option>
                        <option value="yearly">Anual</option>
                        <option value="once">Pago Único</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Modo de Renovación</label>
                    <select name="renewal_mode" id="editRenewalMode"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="manual">Manual</option>
                        <option value="automatic">Automática</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha de Inicio *</label>
                    <input type="date" name="start_date" id="editStartDate" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fecha de Renovación/Vencimiento</label>
                    <input type="date" name="renewal_date" id="editRenewalDate"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Progreso (%)</label>
                    <input type="number" name="progress_percentage" id="editProgressPercentage" min="0" max="100"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Descripción Corta</label>
                    <textarea name="description" id="editDescription" rows="2"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Objetivos / Descripción del Proyecto</label>
                    <textarea name="project_description" id="editProjectDescription" rows="4"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL del Proyecto (Figma, Dev, etc.)</label>
                    <input type="url" name="project_url" id="editProjectUrl"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cobertura Legal / Notas de Riesgo</label>
                    <textarea name="legal_coverage" id="editLegalCoverage" rows="2"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 border-t border-slate-200 dark:border-slate-700 pt-4">
                <button type="button" onclick="closeEditServiceModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Permanent Delete Client Confirmation Modal -->
<div id="permanentDeleteClientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 text-center">
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl text-red-600 dark:text-red-400">warning</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">¿Eliminar Cliente Permanentemente?</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Esta acción es <strong>irreversible</strong> y eliminará todos los servicios, cotizaciones, pagos y documentos asociados a este cliente.
            </p>
            
            <form method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>">
                <input type="hidden" name="action" value="permanent_delete_client">
                <div class="flex gap-3">
                    <button type="button" onclick="closePermanentDeleteClientModal()" 
                            class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Eliminar Todo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div id="updateProgressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="updateProgressModalTitle">Actualizar Progreso</h3>
                <button onclick="closeUpdateProgressModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form id="updateProgressForm" class="p-6">
            <input type="hidden" id="updateProgressServiceId" value="">
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Progreso del Proyecto
                </label>
                <div class="flex items-center gap-4">
                    <input type="range" id="progressSlider" min="0" max="100" value="0" step="1"
                           class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer"
                           oninput="updateProgressDisplay(this.value)">
                    <div class="w-20 text-center">
                        <span id="progressDisplay" class="text-2xl font-bold text-primary">0%</span>
                    </div>
                </div>
                <div class="mt-2 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>0%</span>
                    <span>25%</span>
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-4 overflow-hidden">
                    <div id="progressBarPreview" class="bg-primary h-4 rounded-full transition-all duration-300 flex items-center justify-end pr-2" style="width: 0%">
                        <span id="progressBarText" class="text-xs font-bold text-white"></span>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeUpdateProgressModal()" 
                        class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="paymentModalTitle">Registrar Pago</h3>
                <button onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <form method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>" id="paymentForm" class="p-6">
            <input type="hidden" name="action" value="create_payment_from_service">
            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
            <input type="hidden" name="service_id" id="paymentServiceId" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Servicio
                    </label>
                    <input type="text" id="paymentServiceName" readonly
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="paymentServiceInfo"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Tipo de Pago
                    </label>
                    <select name="payment_type" id="paymentType" onchange="updatePaymentAmount()"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="full">Pago Completo</option>
                        <option value="partial">Adelanto / Pago Parcial</option>
                    </select>
                </div>
                
                <div id="paymentFeeAdsContainer" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Monto Honorarios (Fee)
                        </label>
                        <input type="number" name="fee_amount" id="paymentFeeAmount" step="0.01" min="0" value="0"
                               oninput="sumPaymentAmounts()"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Monto Inversión (Ads)
                        </label>
                        <input type="number" name="ads_amount" id="paymentAdsAmount" step="0.01" min="0" value="0"
                               oninput="sumPaymentAmounts()"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Monto Total *
                    </label>
                    <input type="number" name="amount" id="paymentAmount" step="0.01" min="0" required
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Moneda
                    </label>
                    <select name="currency" id="paymentCurrency"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="MXN">MXN - Peso Mexicano</option>
                        <option value="USD">USD - Dólar</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Método de Pago
                    </label>
                    <select name="payment_method" id="paymentMethod"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="transfer">Transferencia</option>
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="check">Cheque</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Pago *
                    </label>
                    <input type="date" name="payment_date" id="paymentDate" required
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Vencimiento
                    </label>
                    <input type="date" name="due_date" id="paymentDueDate"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Estado
                    </label>
                    <select name="status" id="paymentStatus" onchange="togglePaidAt()"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="pending">Pendiente</option>
                        <option value="paid">Pagado</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>
                
                <div id="paidAtContainer" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Fecha de Pago Realizado
                    </label>
                    <input type="datetime-local" name="paid_at" id="paymentPaidAt"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Número de Referencia
                    </label>
                    <input type="text" name="reference_number" id="paymentReference"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Notas
                    </label>
                    <textarea name="notes" id="paymentNotes" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closePaymentModal()" 
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                    Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Convert Quote Modal -->
<div id="convertQuoteModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">rocket_launch</span>
                Convertir a Servicio
            </h3>
            <button onclick="closeConvertQuoteModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form id="convertQuoteForm" method="POST" action="admin_client_detail.php?id=<?php echo $clientId; ?>" class="p-6 space-y-4">
            <input type="hidden" name="action" value="convert_quote_to_service">
            <input type="hidden" name="quote_id" id="convertQuoteId" value="">
            
            <div class="text-sm text-slate-600 dark:text-slate-400">
                Se creará un servicio activo basado en los ítems de la cotización <span id="convertQuoteNumber" class="font-bold text-slate-900 dark:text-white"></span>.
            </div>

            <!-- Checkbox cargo pendiente -->
            <div class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                <div class="flex items-center h-5">
                    <input id="createPendingPayment" name="create_pending_payment" type="checkbox" value="1" checked
                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary">
                </div>
                <div class="text-sm">
                    <label for="createPendingPayment" class="font-semibold text-slate-900 dark:text-white cursor-pointer">
                        Generar cargo pendiente
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Crea un pago pendiente por el fee mensual con vencimiento en 7 días.
                    </p>
                </div>
            </div>

            <!-- Checkbox incluir inversión (solo visible si la cotización tiene ADS) -->
            <div id="adsConvertFields" class="hidden flex items-start gap-3 p-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-900/10">
                <div class="flex items-center h-5">
                    <input id="paymentIncludesInvestment" name="payment_includes_investment" type="checkbox" value="1" checked
                           class="h-4 w-4 rounded border-amber-300 dark:border-amber-700 text-amber-600 focus:ring-amber-500">
                </div>
                <div class="text-sm">
                    <label for="paymentIncludesInvestment" class="font-semibold text-amber-900 dark:text-amber-200 cursor-pointer">
                        Incluir inversión en Ads
                    </label>
                    <p class="text-xs text-amber-700 dark:text-amber-400">
                        Suma el monto de la inversión ADS al cargo pendiente del primer mes.
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeConvertQuoteModal()"
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition text-sm font-medium">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition text-sm font-bold flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">rocket_launch</span>
                    Convertir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Client Edit Modal Functions
function openClientEditModal() {
    document.getElementById('clientEditModal').classList.remove('hidden');
}

function closeClientEditModal() {
    document.getElementById('clientEditModal').classList.add('hidden');
}

// Payment modal variables - Make sure these are in global scope
var currentServiceId = null;
var currentServiceFee = 0;
var currentPendingBalance = 0;

// Open payment modal from service - Make sure it's in global scope
window.openPaymentModal = function(serviceId, serviceName, monthlyFee, pendingBalance, isAdsService = 0) {
    currentServiceId = serviceId;
    currentServiceFee = monthlyFee;
    currentPendingBalance = pendingBalance;
    
    document.getElementById('paymentModalTitle').textContent = 'Registrar Pago - ' + serviceName;
    document.getElementById('paymentServiceId').value = serviceId;
    document.getElementById('paymentServiceName').value = serviceName;
    
    // Reset form
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentServiceId').value = serviceId;
    document.getElementById('paymentServiceName').value = serviceName;
    
    // Ads fields visibility
    const adsContainer = document.getElementById('paymentFeeAdsContainer');
    if (isAdsService == 1) {
        adsContainer.classList.remove('hidden');
        document.getElementById('paymentFeeAmount').value = monthlyFee.toFixed(2);
        document.getElementById('paymentAdsAmount').value = (Math.max(0, pendingBalance - monthlyFee)).toFixed(2);
        document.getElementById('paymentAmount').value = pendingBalance.toFixed(2);
    } else {
        adsContainer.classList.add('hidden');
        document.getElementById('paymentFeeAmount').value = 0;
        document.getElementById('paymentAdsAmount').value = 0;
        document.getElementById('paymentAmount').value = monthlyFee.toFixed(2);
    }

    document.getElementById('paymentType').value = 'full';
    
    // Update service info
    const infoText = 'Tarifa mensual: $' + monthlyFee.toFixed(2) + ' | Saldo pendiente: $' + Math.max(0, pendingBalance).toFixed(2);
    document.getElementById('paymentServiceInfo').textContent = infoText;
    
    const todayDate = new Date().toISOString().split('T')[0];
    document.getElementById('paymentDate').value = todayDate;
    document.getElementById('paymentStatus').value = 'pending';
    document.getElementById('paidAtContainer').classList.add('hidden');
    
    document.getElementById('paymentModal').classList.remove('hidden');
}

window.sumPaymentAmounts = function() {
    const fee = parseFloat(document.getElementById('paymentFeeAmount').value) || 0;
    const ads = parseFloat(document.getElementById('paymentAdsAmount').value) || 0;
    document.getElementById('paymentAmount').value = (fee + ads).toFixed(2);
}

// Open quick payment modal (without service) - Make sure it's in global scope
window.openQuickPaymentModal = function() {
    currentServiceId = null;
    currentServiceFee = 0;
    currentPendingBalance = 0;
    
    document.getElementById('paymentModalTitle').textContent = 'Registrar Pago Rápido';
    document.getElementById('paymentServiceId').value = '';
    document.getElementById('paymentServiceName').value = 'Pago General';
    document.getElementById('paymentServiceInfo').textContent = 'Pago no asociado a un servicio específico';
    
    // Reset form
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentServiceId').value = '';
    document.getElementById('paymentServiceName').value = 'Pago General';
    document.getElementById('paymentFeeAdsContainer').classList.add('hidden');
    const todayDate = new Date().toISOString().split('T')[0];
    document.getElementById('paymentDate').value = todayDate;
    document.getElementById('paymentStatus').value = 'pending';
    document.getElementById('paidAtContainer').classList.add('hidden');
    
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    currentServiceId = null;
    currentServiceFee = 0;
    currentPendingBalance = 0;
}

function updatePaymentAmount() {
    const paymentType = document.getElementById('paymentType').value;
    const amountInput = document.getElementById('paymentAmount');
    
    if (paymentType === 'full' && currentServiceFee > 0) {
        amountInput.value = currentServiceFee.toFixed(2);
    } else if (paymentType === 'partial' && currentPendingBalance > 0) {
        // Suggest partial payment (50% of pending balance)
        amountInput.value = Math.max(0, (currentPendingBalance * 0.5)).toFixed(2);
    }
}

function togglePaidAt() {
    const status = document.getElementById('paymentStatus').value;
    const container = document.getElementById('paidAtContainer');
    if (status === 'paid') {
        container.classList.remove('hidden');
        if (!document.getElementById('paymentPaidAt').value) {
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            document.getElementById('paymentPaidAt').value = localDateTime;
        }
    } else {
        container.classList.add('hidden');
    }
}

// View service payments modal - Make sure it's in global scope
window.viewServicePayments = function(serviceId, serviceName) {
    // Use the existing currentServiceId variable declared at the top
    currentServiceId = serviceId;
    const modal = document.getElementById('servicePaymentsModal');
    const modalTitle = document.getElementById('servicePaymentsModalTitle');
    const paymentsContainer = document.getElementById('servicePaymentsContainer');
    
    // Set initial title
    modalTitle.textContent = `Pagos - ${serviceName || 'Servicio'}`;
    
    // Show loading state
    paymentsContainer.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-4"></div>
                <p class="text-slate-500 dark:text-slate-400">Cargando pagos...</p>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Load payments via AJAX
    fetch(`admin_client_detail.php?ajax=get_service_payments&service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.payments) {
                // Update title with service name from data if available
                if (data.service_name) {
                    modalTitle.textContent = `Pagos - ${data.service_name}`;
                }
                displayServicePayments(data.payments, data.summary);
            } else {
                paymentsContainer.innerHTML = `
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">inbox</span>
                        <p class="text-slate-500 dark:text-slate-400">${data.message || 'No se encontraron pagos'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading payments:', error);
            paymentsContainer.innerHTML = `
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-4xl text-red-400 mb-2">error</span>
                    <p class="text-red-500 dark:text-red-400">Error al cargar los pagos</p>
                </div>
            `;
        });
}

function displayServicePayments(payments, summary) {
    const container = document.getElementById('servicePaymentsContainer');
    
    if (!payments || payments.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">inbox</span>
                <p class="text-slate-500 dark:text-slate-400">No hay pagos registrados para este servicio</p>
            </div>
        `;
        return;
    }
    
    // Summary cards
    let summaryHtml = '';
    if (summary) {
        summaryHtml = `
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Total Pagado</div>
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">$${parseFloat(summary.total_paid || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Pendiente</div>
                    <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">$${parseFloat(summary.total_pending || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-1">Total</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white">$${parseFloat(summary.total || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</div>
                </div>
            </div>
        `;
    }
    
    // Payments table
    let paymentsHtml = `
        ${summaryHtml}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Montos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Método</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Referencia</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Vencimiento</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
    `;
    
    payments.forEach(payment => {
        const paymentDate = new Date(payment.payment_date);
        const dueDate = payment.due_date ? new Date(payment.due_date) : null;
        const now = new Date();
        
        let statusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        let statusText = 'Pendiente';
        
        if (payment.status === 'paid') {
            statusClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            statusText = 'Pagado';
        } else if (payment.status === 'partially_paid') {
            statusClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
            statusText = 'Pago Parcial';
        } else if (payment.status === 'overdue') {
            statusClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            statusText = 'Vencido';
        } else if (payment.status === 'cancelled') {
            statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            statusText = 'Cancelado';
        }
        
        let dueDateClass = 'text-slate-600 dark:text-slate-400';
        if (dueDate && dueDate < now && payment.status !== 'paid') {
            dueDateClass = 'text-red-600 dark:text-red-400 font-semibold';
        } else if (dueDate) {
            const daysDiff = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
            if (daysDiff <= 7 && daysDiff > 0) {
                dueDateClass = 'text-yellow-600 dark:text-yellow-400';
            }
        }
        
        const clientId = <?php echo $clientId; ?>;
        const serviceId = currentServiceId || (payment.service_id ? payment.service_id : null);
        
        // Use paid_at (date when payment was made) if available, otherwise payment_date
        const displayDate = payment.paid_at ? new Date(payment.paid_at) : paymentDate;
        
        paymentsHtml += `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                    ${displayDate.toLocaleDateString('es-MX')}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">
                        $${parseFloat(payment.amount).toLocaleString('es-MX', {minimumFractionDigits: 2})} ${payment.currency || 'MXN'}
                    </div>
                    <div class="text-xs text-green-600 dark:text-green-400 font-medium mt-1">
                        Abonado: $${parseFloat(payment.paid_amount || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                    </div>
                    <div class="text-xs text-orange-600 dark:text-orange-400 font-medium">
                        Pendiente: $${parseFloat(payment.pending_amount !== undefined ? payment.pending_amount : payment.amount).toLocaleString('es-MX', {minimumFractionDigits: 2})}
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                    ${getPaymentMethodLabel(payment.payment_method)}
                </td>
                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                    ${payment.reference_number || '-'}
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm ${dueDateClass}">
                    ${dueDate ? dueDate.toLocaleDateString('es-MX') : '-'}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button onclick="viewPaymentInvoice(${payment.id}, ${clientId}, ${serviceId || 'null'})" 
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" 
                                title="Ver Factura">
                            <span class="material-symbols-outlined text-lg">receipt</span>
                        </button>
                        <button onclick="downloadPaymentInvoicePDF(${payment.id}, ${clientId}, ${serviceId || 'null'})" 
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                title="Descargar PDF">
                            <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    paymentsHtml += `
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-end">
            <a href="admin_payments.php?client_id=<?php echo $clientId; ?>&service_id=${currentServiceId}" 
               class="text-sm text-primary hover:text-primary/80 flex items-center gap-2">
                Ver todos los pagos
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>
    `;
    
    container.innerHTML = paymentsHtml;
}

function getPaymentMethodLabel(method) {
    const methods = {
        'transfer': 'Transferencia',
        'cash': 'Efectivo',
        'card': 'Tarjeta',
        'check': 'Cheque',
        'other': 'Otro'
    };
    return methods[method] || method;
}

function closeServicePaymentsModal() {
    document.getElementById('servicePaymentsModal').classList.add('hidden');
    currentServiceId = null;
}

// --- ADS SERVICES MANAGEMENT ---

// Service Costs/Investment Handlers
function openServiceCostsModal(serviceId, clientId, serviceName) {
    const modal = document.getElementById('serviceCostsModal');
    const title = document.getElementById('serviceCostsModalTitle');
    const serviceIdInput = document.getElementById('costServiceId');
    const clientIdInput = document.getElementById('costClientId');
    
    if (modal && title && serviceIdInput) {
        serviceIdInput.value = serviceId;
        if (clientIdInput) clientIdInput.value = clientId;
        title.textContent = 'Inversiones Adicionales (Ads) - ' + serviceName;
        
        // Reset form
        const form = document.getElementById('serviceCostForm');
        if (form) {
            form.reset();
            // Restore hidden values
            document.getElementById('costServiceId').value = serviceId;
            if (document.getElementById('costClientId')) document.getElementById('costClientId').value = clientId;
            // Defaults
            const today = new Date().toISOString().split('T')[0];
            const periodDate = document.getElementById('costPeriodDate');
            if (periodDate) periodDate.value = today;
            const platform = document.getElementById('costPlatform');
            if (platform) platform.value = 'meta';
        }

        modal.classList.remove('hidden');
        
        // Load existing costs
        loadServiceCosts(serviceId);
    }
}

function closeServiceCostsModal() {
    const modal = document.getElementById('serviceCostsModal');
    if (modal) modal.classList.add('hidden');
}

function loadServiceCosts(serviceId) {
    const listContainer = document.getElementById('serviceCostsList');
    if (!listContainer) return;

    listContainer.innerHTML = '<div class="text-center py-8"><span class="material-symbols-outlined animate-spin text-primary">sync</span><p class="text-sm text-slate-500 mt-2">Cargando historial de inversiones...</p></div>';

    // Usamos el handler interno de AJAX para mayor confiabilidad de sesión
    fetch(`admin_client_detail.php?ajax=get_service_costs&service_id=${serviceId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                return response.text().then(text => {
                    console.error("Expected JSON but got:", text);
                    throw new Error("Respuesta del servidor no es válida.");
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.costs && data.costs.length > 0) {
                let html = '<div class="space-y-3">';
                let totalAdicional = 0;
                
                const platformLabels = {
                    'meta': 'META (FB/IG)',
                    'facebook': 'META',
                    'whatsapp': 'WhatsApp',
                    'google': 'Google Ads',
                    'tiktok': 'TikTok',
                    'linkedin': 'LinkedIn',
                    'other': 'Otra'
                };

                data.costs.forEach(inv => {
                    const amount = parseFloat(inv.amount);
                    totalAdicional += amount;
                    const date = new Date(inv.transaction_date).toLocaleDateString('es-MX', {day: '2-digit', month: 'short', year: 'numeric'});
                    
                    html += `
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-primary/30 transition-colors shadow-sm">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-lg font-bold text-slate-900 dark:text-white">$${amount.toLocaleString('es-MX', {minimumFractionDigits: 2})}</span>
                                    <span class="text-[10px] px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-full font-bold uppercase tracking-wider">
                                        ${platformLabels[inv.platform] || inv.platform || 'N/A'}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-500 font-medium">
                                    <span class="material-symbols-outlined text-[14px] align-middle mr-1">calendar_today</span>${date}
                                </div>
                                ${inv.description ? `<p class="text-xs text-slate-600 dark:text-slate-400 mt-2 italic leading-relaxed border-l-2 border-slate-200 dark:border-slate-700 pl-3">${inv.description}</p>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                html += `
                    <div class="mt-4 p-4 bg-primary/5 dark:bg-primary/10 rounded-xl border border-primary/20">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight">Total Adicional Registrado:</span>
                            <span class="text-xl font-black text-primary">$${totalAdicional.toLocaleString('es-MX', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                </div>`;
                
                listContainer.innerHTML = html;
            } else {
                listContainer.innerHTML = `
                    <div class="text-center py-10 px-4">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 text-3xl">payments</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">No hay inversiones adicionales manuales registradas para este servicio.</p>
                        <p class="text-[10px] text-slate-400 mt-1 italic">Las inversiones desde pagos se ven en el historial completo.</p>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error loading investments:', error);
            listContainer.innerHTML = '<div class="p-4 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg text-center text-sm text-red-600 dark:text-red-400">Error al cargar el historial. Intente de nuevo.</div>';
        });
}

// Global Initialization Block
document.addEventListener('DOMContentLoaded', function() {
    // 1. Service Cost Form Handler
    const serviceCostForm = document.getElementById('serviceCostForm');
    if (serviceCostForm) {
        serviceCostForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn ? submitBtn.innerHTML : '';
            
            // Validations
            const serviceId = formData.get('service_id');
            const amount = parseFloat(formData.get('amount') || 0);
            
            if (!serviceId || amount <= 0) {
                alert('Por favor, ingresa un monto válido mayor a cero.');
                return;
            }
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm mr-2">sync</span>Guardando...';
            }
            
            fetch('api_add_investment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    // Reset and refresh
                    this.reset();
                    document.getElementById('costServiceId').value = serviceId;
                    
                    // Show success state briefly
                    if (submitBtn) {
                        submitBtn.classList.replace('bg-primary', 'bg-green-600');
                        submitBtn.innerHTML = '<span class="material-symbols-outlined mr-2">check_circle</span>¡Registrado!';
                    }
                    
                    setTimeout(() => {
                        loadServiceCosts(serviceId);
                        if (typeof updateAdsInvestmentTotal === 'function') {
                            updateAdsInvestmentTotal(serviceId);
                        }
                        // Re-enable button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalContent;
                            submitBtn.classList.replace('bg-green-600', 'bg-primary');
                        }
                        
                        // Optional: reload page to update all summaries
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la inversión'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalContent;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error crítico al procesar la solicitud');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                }
            });
        });
    }

    // 2. Edit Service Modal Listeners
    const editServiceType = document.getElementById('editServiceType');
    const editIsAdsService = document.getElementById('editIsAdsService');
    const editAdsFields = document.getElementById('editAdsFields');

    if (editServiceType && editAdsFields) {
        editServiceType.addEventListener('change', function() {
            if (this.value === 'ads' || this.value.includes('ads_')) {
                editAdsFields.classList.remove('hidden');
                if (editIsAdsService) editIsAdsService.checked = true;
            } else {
                if (editIsAdsService && !editIsAdsService.checked) {
                    editAdsFields.classList.add('hidden');
                }
            }
        });
    }

    if (editIsAdsService && editAdsFields) {
        editIsAdsService.addEventListener('change', function() {
            if (this.checked) {
                editAdsFields.classList.remove('hidden');
            } else {
                editAdsFields.classList.add('hidden');
            }
        });
    }

    // 3. URL Parameter Handling (open_service_costs)
    const urlParams = new URLSearchParams(window.location.search);
    const openServiceCostsId = urlParams.get('open_service_costs');
    if (openServiceCostsId) {
        const serviceRow = document.querySelector(`tr[data-service-id="${openServiceCostsId}"]`);
        if (serviceRow) {
            const serviceName = serviceRow.querySelector('td:first-child')?.textContent?.trim() || 'Servicio';
            const clientId = urlParams.get('id'); // ID del cliente desde la URL
            setTimeout(() => {
                openServiceCostsModal(openServiceCostsId, clientId, serviceName);
            }, 500);
        }
    }
});

// Function to update Ads investment total in the main table
function updateAdsInvestmentTotal(serviceId) {
    if (!serviceId) return;
    
    fetch(`admin_client_detail.php?ajax=get_service_investment&service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.investment !== undefined) {
                // Find all cells that might display this
                const rows = document.querySelectorAll('table tbody tr');
                for (let row of rows) {
                    const actionsCell = row.querySelector('td:last-child');
                    if (!actionsCell) continue;
                    
                    const button = actionsCell.querySelector('button[onclick*="openServiceCostsModal"]');
                    if (button && button.getAttribute('onclick').includes(serviceId)) {
                        const tarifaCell = row.querySelector('td:nth-child(2)');
                        if (tarifaCell) {
                            let adsDisplay = tarifaCell.querySelector('.ads-total-display');
                            if (!adsDisplay) {
                                adsDisplay = document.createElement('div');
                                adsDisplay.className = 'ads-total-display text-[10px] font-bold text-blue-600 dark:text-blue-400 mt-1 flex items-center gap-1';
                                tarifaCell.appendChild(adsDisplay);
                            }
                            
                            if (parseFloat(data.investment) > 0) {
                                adsDisplay.innerHTML = `<span class="material-symbols-outlined notranslate text-[12px]">campaign</span> Ads $${parseFloat(data.investment).toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
                                adsDisplay.style.display = 'flex';
                            } else {
                                adsDisplay.style.display = 'none';
                            }
                        }
                        break;
                    }
                }
            }
        })
        .catch(err => console.error('Error updating investment total:', err));
}

function viewAllTransactions() {
    const serviceId = document.getElementById('costServiceId').value;
    if (!serviceId) {
        alert('No hay servicio seleccionado');
        return;
    }
    openAllTransactionsModal(serviceId);
}


function openAllTransactionsModal(serviceId) {
    const modal = document.getElementById('allTransactionsModal');
    const title = document.getElementById('allTransactionsModalTitle');
    const summaryDiv = document.getElementById('transactionsSummary');
    const listDiv = document.getElementById('allTransactionsList');
    
    listDiv.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Cargando transacciones...</p>';
    summaryDiv.innerHTML = '';
    
    // Get service name from modal title
    const serviceName = document.getElementById('serviceCostsModalTitle').textContent.replace('Inversiones Adicionales - Campañas de Ads - ', '');
    title.textContent = `Historial Completo - ${serviceName}`;
    
    // Fetch all transactions
    fetch(`?ajax=get_all_transactions&service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions) {
                // Display summary
                const summary = data.summary || { total_fees: 0, total_investment: 0 };
                
                summaryDiv.innerHTML = `
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-xs text-green-700 dark:text-green-300 mb-1">Honorarios de Gestión</div>
                        <div class="text-xl font-bold text-green-900 dark:text-green-100">$${summary.total_fees.toFixed(2)}</div>
                    </div>
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">Inversión Publicitaria</div>
                        <div class="text-xl font-bold text-blue-900 dark:text-blue-100">$${summary.total_investment.toFixed(2)}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Total acumulado</div>
                    </div>
                `;
                
                // Display transactions (solo inversiones, no consumos)
                const filteredTransactions = data.transactions.filter(tx => 
                    tx.transaction_type === 'income_fee' || tx.transaction_type === 'income_ads'
                );
                
                if (filteredTransactions.length > 0) {
                    let html = '';
                    const typeLabels = {
                        'income_fee': { label: 'Honorarios', color: 'green', icon: 'ðŸ’°' },
                        'income_ads': { label: 'Inversión', color: 'blue', icon: 'ðŸ’µ' }
                        // expense_ads_consumed ya no se usa - no se registran consumos
                    };
                    
                    const platformLabels = {
                        'meta': 'META',
                        'facebook': 'META', // Mantener compatibilidad con registros antiguos
                        'whatsapp': 'WhatsApp META',
                        'google': 'Google',
                        'tiktok': 'TikTok',
                        'linkedin': 'LinkedIn',
                        'other': 'Otra'
                    };
                    
                    filteredTransactions.forEach(tx => {
                        const txType = typeLabels[tx.transaction_type] || { label: tx.transaction_type, color: 'gray', icon: 'ðŸ“' };
                        const amount = parseFloat(tx.amount);
                        const isNegative = amount < 0;
                        const date = new Date(tx.transaction_date);
                        
                        html += `
                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-lg">${txType.icon}</span>
                                            <span class="text-sm font-semibold text-${txType.color}-700 dark:text-${txType.color}-300">${txType.label}</span>
                                            ${tx.platform ? `<span class="text-xs px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded">${platformLabels[tx.platform] || tx.platform}</span>` : ''}
                                        </div>
                                        ${tx.description ? `<p class="text-sm text-slate-700 dark:text-slate-300 mb-1">${tx.description}</p>` : ''}
                                        ${tx.payment_number ? `<p class="text-xs text-slate-500 dark:text-slate-400">Pago: ${tx.payment_number}</p>` : ''}
                                        ${tx.billing_period_start && tx.billing_period_end ? `
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                Período: ${new Date(tx.billing_period_start).toLocaleDateString('es-MX')} - ${new Date(tx.billing_period_end).toLocaleDateString('es-MX')}
                                            </p>
                                        ` : ''}
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-lg font-bold ${isNegative ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400'}">
                                            ${isNegative ? '-' : '+'}$${Math.abs(amount).toFixed(2)}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            ${date.toLocaleDateString('es-MX')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    listDiv.innerHTML = html;
                } else {
                    listDiv.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No hay transacciones registradas</p>';
                }
            } else {
                listDiv.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400 text-center py-4">Error al cargar las transacciones</p>';
            }
        })
        .catch(error => {
            console.error('Error loading all transactions:', error);
            listDiv.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400 text-center py-4">Error al cargar las transacciones</p>';
        });
    
    modal.classList.remove('hidden');
}

function closeAllTransactionsModal() {
    document.getElementById('allTransactionsModal').classList.add('hidden');
}

// Validate service cost form
function validateServiceCostForm() {
    const amount = parseFloat(document.getElementById('costAmount').value);
    const serviceId = document.getElementById('costServiceId').value;
    
    if (!serviceId || serviceId <= 0) {
        alert('Error: No se ha seleccionado un servicio');
        return false;
    }
    
    if (!amount || amount <= 0) {
        alert('Error: El presupuesto debe ser mayor a cero');
        return false;
    }
    
    return true;
}

document.getElementById('serviceCostsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceCostsModal();
    }
});

// Close modals on outside click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

document.getElementById('servicePaymentsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeServicePaymentsModal();
    }
});

// Update Progress Modal Functions
function openUpdateProgressModal(serviceId, serviceName, currentProgress, isProject) {
    document.getElementById('updateProgressServiceId').value = serviceId;
    document.getElementById('updateProgressModalTitle').textContent = 'Actualizar Progreso - ' + serviceName;
    document.getElementById('progressSlider').value = currentProgress;
    updateProgressDisplay(currentProgress);
    document.getElementById('updateProgressModal').classList.remove('hidden');
}

function closeUpdateProgressModal() {
    document.getElementById('updateProgressModal').classList.add('hidden');
}

function updateProgressDisplay(value) {
    const progress = parseInt(value);
    document.getElementById('progressDisplay').textContent = progress + '%';
    document.getElementById('progressBarPreview').style.width = progress + '%';
    if (progress >= 50) {
        document.getElementById('progressBarText').textContent = progress + '%';
    } else {
        document.getElementById('progressBarText').textContent = '';
    }
}

// Handle progress form submission
document.getElementById('updateProgressForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const serviceId = document.getElementById('updateProgressServiceId').value;
    const progress = document.getElementById('progressSlider').value;
    
    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('progress', progress);
    
    fetch('admin_client_detail.php?ajax=update_progress', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show updated progress
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo actualizar el progreso'));
        }
    })
    .catch(error => {
        console.error('Error updating progress:', error);
        alert('Error al actualizar el progreso');
    });
});

document.getElementById('updateProgressModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUpdateProgressModal();
    }
});

// WhatsApp Modal Functions
function openWhatsAppModal(clientId, clientName, whatsappFull, whatsappClean) {
    document.getElementById('whatsapp-client-id').value = clientId;
    document.getElementById('whatsapp-phone').value = whatsappClean;
    document.getElementById('whatsapp-message').value = '';
    document.getElementById('whatsapp-message-status').classList.add('hidden');
    document.getElementById('whatsapp-char-count').textContent = '0 / 4096 caracteres';
    
    // Reset template selection
    document.getElementById('whatsapp-template-toggle').checked = false;
    document.getElementById('whatsapp-template-select').value = '';
    toggleWhatsAppMessageType();
    
    // Show client info
    const contentHtml = `
        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Enviar a:</p>
            <p class="font-semibold text-lg text-slate-900 dark:text-white">${clientName}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">${whatsappFull}</p>
        </div>
    `;
    document.getElementById('whatsapp-message-content').innerHTML = contentHtml;
    
    document.getElementById('whatsapp-modal').classList.remove('hidden');
    document.getElementById('whatsapp-message').focus();
}

function closeWhatsAppModal() {
    document.getElementById('whatsapp-modal').classList.add('hidden');
    document.getElementById('whatsapp-form').reset();
    // Reset to free message mode
    document.getElementById('whatsapp-template-toggle').checked = false;
    toggleWhatsAppMessageType();
}

function toggleWhatsAppMessageType() {
    const useTemplate = document.getElementById('whatsapp-template-toggle').checked;
    const templateSection = document.getElementById('whatsapp-template-section');
    const freeMessageSection = document.getElementById('whatsapp-free-message-section');
    const typeLabel = document.getElementById('whatsapp-type-label');
    const useTemplateInput = document.getElementById('whatsapp-use-template');
    
    if (useTemplate) {
        templateSection.classList.remove('hidden');
        freeMessageSection.classList.add('hidden');
        typeLabel.textContent = 'Usar Plantilla';
        useTemplateInput.value = '1';
    } else {
        templateSection.classList.add('hidden');
        freeMessageSection.classList.remove('hidden');
        typeLabel.textContent = 'Mensaje Libre';
        useTemplateInput.value = '0';
    }
}

// Character counter for WhatsApp message
document.getElementById('whatsapp-message')?.addEventListener('input', function() {
    const length = this.value.length;
    const maxLength = 4096;
    const charCount = document.getElementById('whatsapp-char-count');
    
    charCount.textContent = `${length} / ${maxLength} caracteres`;
    
    if (length > maxLength) {
        charCount.classList.add('text-red-600', 'dark:text-red-400');
        charCount.classList.remove('text-slate-500', 'dark:text-slate-400');
    } else {
        charCount.classList.remove('text-red-600', 'dark:text-red-400');
        charCount.classList.add('text-slate-500', 'dark:text-slate-400');
    }
});

// Handle WhatsApp form submission
document.getElementById('whatsapp-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const sendBtn = document.getElementById('whatsapp-send-btn');
    const btnText = document.getElementById('whatsapp-btn-text');
    const btnLoading = document.getElementById('whatsapp-btn-loading');
    const statusDiv = document.getElementById('whatsapp-message-status');
    
    // Disable button and show loading
    sendBtn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    statusDiv.classList.add('hidden');
    
    const formData = new FormData(this);
    
    // For clients, we need to send to a different endpoint or adapt the existing one
    // For now, we'll use the same send_whatsapp.php but with client_id instead of lead_id
    fetch('send_whatsapp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
            statusDiv.innerHTML = `<p class="text-green-800 dark:text-green-300">${data.message || 'Mensaje enviado exitosamente'}</p>`;
            statusDiv.classList.remove('hidden');
            
            // Reset form after 2 seconds
            setTimeout(() => {
                closeWhatsAppModal();
            }, 2000);
        } else {
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
            statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">${data.message || 'Error al enviar el mensaje'}</p>`;
            statusDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error sending WhatsApp message:', error);
        statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
        statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">Error al enviar el mensaje. Por favor, intenta de nuevo.</p>`;
        statusDiv.classList.remove('hidden');
    })
    .finally(() => {
        // Re-enable button
        sendBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    });
});

document.getElementById('whatsapp-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeWhatsAppModal();
    }
});
</script>

<!-- WhatsApp Send Message Modal -->
<div id="whatsapp-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-green-600 dark:text-green-400">chat</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Enviar Mensaje por WhatsApp</h3>
                </div>
                <button onclick="closeWhatsAppModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
        </div>
        
        <div id="whatsapp-message-content" class="p-6 space-y-4">
            <!-- Client info will be inserted here -->
        </div>
        
        <form id="whatsapp-form" class="p-6 border-t border-slate-200 dark:border-slate-700">
            <input type="hidden" id="whatsapp-client-id" name="client_id">
            <input type="hidden" id="whatsapp-phone" name="phone">
            <input type="hidden" id="whatsapp-use-template" name="use_template" value="0">
            <input type="hidden" id="whatsapp-template-name" name="template_name" value="">
            <input type="hidden" id="whatsapp-template-params" name="template_params" value="[]">
            
            <!-- Toggle between free message and template -->
            <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de mensaje:</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Mensaje libre solo funciona dentro de 24h después del último mensaje del usuario</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="whatsapp-template-toggle" class="sr-only peer" onchange="toggleWhatsAppMessageType()">
                        <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300" id="whatsapp-type-label">Mensaje Libre</span>
                    </label>
                </div>
            </div>
            
            <!-- Template selection (hidden by default) -->
            <div id="whatsapp-template-section" class="hidden mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Seleccionar Plantilla:</label>
                <select id="whatsapp-template-select" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Selecciona una plantilla --</option>
                    <option value="recordatorio">Recordatorio</option>
                    <option value="tes_unomedic">tes_unomedic</option>
                    <option value="recordatorio_cita">recordatorio_cita</option>
                    <option value="appointment_confirmation_1">appointment_confirmation_1</option>
                    <option value="appointment_cancellation_1">appointment_cancellation_1</option>
                </select>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Las plantillas funcionan fuera de la ventana de 24 horas</p>
            </div>
            
            <!-- Free message textarea (shown by default) -->
            <div id="whatsapp-free-message-section" class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Mensaje:</label>
                <textarea id="whatsapp-message" name="message" rows="6"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Escribe tu mensaje aquí..."></textarea>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Máximo 4096 caracteres. Solo funciona dentro de 24h después del último mensaje del usuario.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400" id="whatsapp-char-count">0 / 4096 caracteres</p>
            </div>
            
            <div id="whatsapp-message-status" class="hidden mb-4 p-4 rounded-lg"></div>
            
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeWhatsAppModal()" 
                        class="px-6 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" id="whatsapp-send-btn"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span class="material-symbols-outlined">send</span>
                    <span id="whatsapp-btn-text">Enviar Mensaje</span>
                    <span id="whatsapp-btn-loading" class="hidden">
                        <span class="material-symbols-outlined animate-spin">sync</span> Enviando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>


<script>

// Confirm delete service
function confirmDeleteService(serviceId, serviceName) {
    // Create popup/modal
    if (confirm(`¿Estás seguro de que deseas eliminar el servicio "${serviceName}"?\n\nEsta acción NO se puede deshacer. Solo se pueden eliminar servicios que no tengan pagos registrados.`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_client_detail.php?id=<?php echo $clientId; ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_service';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'service_id';
        idInput.value = serviceId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Toggle Services View
function toggleServicesView(view) {
    const activeList = document.getElementById('active-services-list');
    const finishedList = document.getElementById('finished-services-list');
    const activeBtn = document.getElementById('btn-active-services');
    const finishedBtn = document.getElementById('btn-finished-services');
    
    if (view === 'active') {
        activeList.classList.remove('hidden');
        finishedList.classList.add('hidden');
        
        activeBtn.classList.add('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        activeBtn.classList.remove('text-slate-500', 'hover:text-slate-700', 'dark:text-slate-400', 'dark:hover:text-slate-300');
        
        finishedBtn.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        finishedBtn.classList.add('text-slate-500', 'hover:text-slate-700', 'dark:text-slate-400', 'dark:hover:text-slate-300');
    } else {
        activeList.classList.add('hidden');
        finishedList.classList.remove('hidden');
        
        activeBtn.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        activeBtn.classList.add('text-slate-500', 'hover:text-slate-700', 'dark:text-slate-400', 'dark:hover:text-slate-300');
        
        finishedBtn.classList.add('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        finishedBtn.classList.remove('text-slate-500', 'hover:text-slate-700', 'dark:text-slate-400', 'dark:hover:text-slate-300');
    }
}

// Invoice Modal Functions
function openInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.remove('hidden');
        // Load client services
        loadClientServices();
    }
}

function previewInvoice() {
    const form = document.getElementById('send-invoice-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const clientId = formData.get('client_id');
    const invoiceType = formData.get('invoice_type');
    const serviceId = invoiceType === 'service' ? formData.get('service_id') : '';
    
    if (!clientId) {
        alert('Por favor, selecciona un cliente primero');
        return;
    }
    
    const includeFinished = formData.get('include_finished') === '1' ? '1' : '0';
    
    // Build preview URL (HTML format)
    let previewUrl = `generate_invoice.php?client_id=${clientId}&format=html&include_finished=${includeFinished}`;
    if (serviceId) {
        previewUrl += `&service_id=${serviceId}`;
    }
    
    // Open in new window
    window.open(previewUrl, '_blank', 'width=900,height=800,scrollbars=yes,resizable=yes');
}

function downloadInvoicePDF() {
    const form = document.getElementById('send-invoice-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const clientId = formData.get('client_id');
    const invoiceType = formData.get('invoice_type');
    const serviceId = invoiceType === 'service' ? formData.get('service_id') : '';
    
    if (!clientId) {
        alert('Por favor, selecciona un cliente primero');
        return;
    }
    
    const includeFinished = formData.get('include_finished') === '1' ? '1' : '0';
    
    // Build PDF download URL
    let pdfUrl = `generate_invoice.php?client_id=${clientId}&format=pdf&include_finished=${includeFinished}`;
    if (serviceId) {
        pdfUrl += `&service_id=${serviceId}`;
    }
    
    // Download PDF
    window.location.href = pdfUrl;
}

function closeInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// View Payment Invoice
function viewPaymentInvoice(paymentId, clientId, serviceId) {
    let url = `generate_invoice.php?client_id=${clientId}&format=html`;
    if (paymentId) {
        url += `&payment_id=${paymentId}`;
    }
    if (serviceId && serviceId !== 'null' && serviceId !== null) {
        url += `&service_id=${serviceId}`;
    }
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes,resizable=yes');
}

// Download Payment Invoice PDF
function downloadPaymentInvoicePDF(paymentId, clientId, serviceId) {
    let url = `generate_invoice.php?client_id=${clientId}&format=pdf`;
    if (paymentId) {
        url += `&payment_id=${paymentId}`;
    }
    if (serviceId && serviceId !== 'null' && serviceId !== null) {
        url += `&service_id=${serviceId}`;
    }
    // Download PDF
    window.location.href = url;
}

function loadClientServices() {
    const clientId = <?php echo $clientId; ?>;
    const serviceSelect = document.getElementById('invoice-service-id');
    
    if (!serviceSelect) return;
    
    // Fetch services via AJAX
    fetch(`admin_client_detail.php?ajax=get_client_services&client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.services) {
                serviceSelect.innerHTML = '<option value="">Seleccionar proyecto...</option>';
                
                const activeServices = data.services.filter(s => s.status === 'active');
                const finishedServices = data.services.filter(s => s.status !== 'active');
                
                if (activeServices.length > 0) {
                    const groupActive = document.createElement('optgroup');
                    groupActive.label = 'Servicios Activos';
                    activeServices.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.service_name;
                        groupActive.appendChild(option);
                    });
                    serviceSelect.appendChild(groupActive);
                }
                
                if (finishedServices.length > 0) {
                    const groupFinished = document.createElement('optgroup');
                    groupFinished.label = 'Servicios Concluidos / Historial';
                    finishedServices.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.service_name + ' (Concluido)';
                        groupFinished.appendChild(option);
                    });
                    serviceSelect.appendChild(groupFinished);
                }
            }
        })
        .catch(error => {
            console.error('Error loading services:', error);
        });
}

// Send invoice
document.getElementById('send-invoice-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const sendBtn = document.getElementById('send-invoice-btn');
    const btnText = sendBtn.querySelector('.btn-text');
    const btnLoading = sendBtn.querySelector('.btn-loading');
    const statusDiv = document.getElementById('invoice-status');
    
    // Disable button and show loading
    sendBtn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    statusDiv.classList.add('hidden');
    
    fetch('send_invoice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message;
            if (data.payments_updated !== undefined) {
                message += ` (${data.payments_updated} de ${data.payments_found} pagos actualizados)`;
            }
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
            statusDiv.innerHTML = `<p class="text-green-800 dark:text-green-300">${message}</p>`;
            statusDiv.classList.remove('hidden');
            
            // Reset form and reload page after 2 seconds to show updated data
            setTimeout(() => {
                closeInvoiceModal();
                this.reset();
                // Reload page to show updated invoice status
                window.location.reload();
            }, 2000);
        } else {
            statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
            statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">${data.message || 'Error al enviar el recibo'}</p>`;
            statusDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error sending invoice:', error);
        statusDiv.className = 'mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
        statusDiv.innerHTML = `<p class="text-red-800 dark:text-red-300">Error al enviar el recibo. Por favor, intenta de nuevo.</p>`;
        statusDiv.classList.remove('hidden');
    })
    .finally(() => {
        sendBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    });
});

document.getElementById('invoice-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeInvoiceModal();
    }
});
</script>

<!-- Invoice Send Modal -->
<div id="invoice-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl text-slate-600 dark:text-slate-400">receipt</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Enviar Factura/Recibo</h3>
                </div>
                <button onclick="closeInvoiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
        </div>
        
        <form id="send-invoice-form" class="p-6 space-y-4">
            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Tipo de Resumen
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="invoice_type" value="all" checked class="mr-2" onchange="document.getElementById('invoice-service-id').disabled = true;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Todos los proyectos</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="invoice_type" value="service" class="mr-2" onchange="document.getElementById('invoice-service-id').disabled = false;">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Proyecto específico</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label for="invoice-service-id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Proyecto
                </label>
                <select id="invoice-service-id" name="service_id" disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Cargando proyectos...</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Incluir
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="include_paid" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Pagos realizados</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_pending" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Pagos pendientes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_services" value="1" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Detalle de servicios</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_finished" value="1" class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Incluir servicios concluidos</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Método de envío
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="whatsapp" checked class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">WhatsApp</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="email" class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Email</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="send_via" value="both" class="mr-2">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Ambos</span>
                    </label>
                </div>
            </div>
            
            <div id="invoice-status" class="hidden"></div>
            
            <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex gap-2">
                    <button type="button" onclick="previewInvoice()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">visibility</span>
                        Vista Previa
                    </button>
                    <button type="button" onclick="downloadInvoicePDF()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        Descargar PDF
                    </button>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" id="send-invoice-btn" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <span class="btn-text">Generar y Enviar</span>
                        <span class="btn-loading hidden">
                            <span class="material-symbols-outlined animate-spin text-lg">sync</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



<!-- Service Payments Modal -->

<div id="service-payments-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">

    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">

        <div class="p-6 border-b border-slate-200 dark:border-slate-700">

            <div class="flex items-center justify-between">

                <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="service-payments-title">Pagos del Servicio</h3>

                <button onclick="closeServicePaymentsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">

                    <span class="material-symbols-outlined text-2xl">close</span>

                </button>

            </div>

        </div>

        <div id="service-payments-content" class="p-6">

            <!-- Content will be loaded here -->

        </div>

    </div>

</div>



<script>

function viewServicePayments(serviceId, serviceName) {

    const modal = document.getElementById('service-payments-modal');

    const content = document.getElementById('service-payments-content');

    const title = document.getElementById('service-payments-title');

    

    if (!modal || !content) return;

    

    title.textContent = 'Pagos - ' + serviceName;

    content.innerHTML = '<div class="flex justify-center py-8"><span class="material-symbols-outlined animate-spin text-3xl text-primary">sync</span></div>';

    

    modal.classList.remove('hidden');

    

    // Fetch payments

    fetch(`admin_client_detail.php?ajax=get_service_payments&service_id=${serviceId}`)

        .then(response => response.json())

        .then(data => {

            if (data.status === 'error' || data.success === false) {

                content.innerHTML = `<div class="text-red-500 text-center">${data.message || 'Error al cargar los pagos'}</div>`;

                return;

            }

            

            // Format helpers

            const formatMoney = (amount) => {

                return parseFloat(amount).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            };

            

            const formatDate = (dateStr) => {

                if (!dateStr) return '-';

                const date = new Date(dateStr);

                return date.toLocaleDateString('es-MX');

            };

            

            // Render Summary

            let html = `

                <div class="grid grid-cols-2 gap-4 mb-6">

                    <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg text-center border border-green-100 dark:border-green-800">

                        <div class="text-xs text-green-800 dark:text-green-300 mb-1">Total Pagado</div>

                        <div class="text-lg font-bold text-green-600 dark:text-green-400">$${formatMoney(data.summary.total_paid || 0)}</div>

                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg text-center border border-yellow-100 dark:border-yellow-800">

                        <div class="text-xs text-yellow-800 dark:text-yellow-300 mb-1">Total Pendiente</div>

                        <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">$${formatMoney(data.summary.total_pending || 0)}</div>

                    </div>

                </div>

            `;

            

            // Render List

            if (data.payments && data.payments.length > 0) {

                html += `

                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">

                        <table class="w-full text-sm text-left">

                            <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">

                                <tr>

                                    <th class="px-4 py-2 font-medium">Fecha</th>

                                    <th class="px-4 py-2 font-medium text-right">Monto</th>

                                    <th class="px-4 py-2 font-medium text-center">Estado</th>

                                     <th class="px-4 py-2 font-medium text-center">Factura</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">

                `;

                

                data.payments.forEach(payment => {

                    const statusClass = payment.status === 'paid' 

                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' 

                        : (payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-gray-100 text-gray-800');

                        

                    const statusLabel = payment.status === 'paid' ? 'Pagado' : (payment.status === 'pending' ? 'Pendiente' : payment.status);

                    

                    html += `

                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">

                            <td class="px-4 py-3 text-slate-900 dark:text-white">${formatDate(payment.payment_date)}</td>

                            <td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">$${formatMoney(payment.amount)}</td>

                            <td class="px-4 py-3 text-center">

                                <span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}">

                                    ${statusLabel}

                                </span>

                            </td>

                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                   <div class="invoice-dropdown">
                                       <button type="button" onclick="event.stopPropagation(); this.parentElement.classList.toggle('active')" class="text-blue-500 hover:text-blue-700 p-1" title="Factura / Recibo">
                                           <span class="material-symbols-outlined text-base">receipt_long</span>
                                       </button>
                                       <div class="invoice-dropdown-content">
                                           <a href="generate_invoice.php?client_id=${payment.client_id}&payment_id=${payment.id}&format=pdf" target="_blank">
                                               <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                                               Descargar PDF
                                           </a>
                                           <button type="button" onclick="sendPaymentWhatsApp(${payment.id}, ${payment.client_id}, ${payment.service_id}, 'whatsapp')">
                                               <span class="material-symbols-outlined text-sm">send</span>
                                               Enviar a WhatsApp
                                           </button>
                                           <button type="button" onclick="sendPaymentWhatsApp(${payment.id}, ${payment.client_id}, ${payment.service_id}, 'email')">
                                               <span class="material-symbols-outlined text-sm">mail</span>
                                               Enviar por Correo
                                           </button>
                                           <button type="button" onclick="sendPaymentWhatsApp(${payment.id}, ${payment.client_id}, ${payment.service_id}, 'both')">
                                               <span class="material-symbols-outlined text-sm">checklist</span>
                                               Enviar por Ambos
                                           </button>
                                       </div>
                                   </div>
                                </div>
                            </td>

                        </tr>

                    `;

                });

                

                html += `</tbody></table></div>`;

            } else {

                html += `<div class="text-center py-8 text-slate-500 dark:text-slate-400">No hay pagos registrados para este servicio.</div>`;

            }

            

            content.innerHTML = html;

        })

        .catch(error => {

            console.error('Error:', error);

            content.innerHTML = `<div class="text-red-500 text-center">Error de conexión al cargar pagos</div>`;

        });

}



function closeServicePaymentsModal() {

    const modal = document.getElementById('service-payments-modal');

    if (modal) modal.classList.add('hidden');

}



// Close modal when clicking outside

document.getElementById('service-payments-modal').addEventListener('click', function(e) {

    if (e.target === this) {

        closeServicePaymentsModal();

    }

});

// Finance Table Toggle
function toggleFinanceView(view) {
    const activeTable = document.getElementById('finance-active-table');
    const finishedTable = document.getElementById('finance-finished-table');
    const activeBtn = document.getElementById('btn-finance-active');
    const finishedBtn = document.getElementById('btn-finance-finished');

    if (!activeTable || !finishedTable || !activeBtn || !finishedBtn) return;

    if (view === 'active') {
        activeTable.classList.remove('hidden');
        finishedTable.classList.add('hidden');
        activeBtn.classList.add('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        activeBtn.classList.remove('text-slate-500', 'dark:text-slate-400');
        finishedBtn.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        finishedBtn.classList.add('text-slate-500', 'dark:text-slate-400');
    } else {
        activeTable.classList.add('hidden');
        finishedTable.classList.remove('hidden');
        finishedBtn.classList.add('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        finishedBtn.classList.remove('text-slate-500', 'dark:text-slate-400');
        activeBtn.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'font-medium');
        activeBtn.classList.add('text-slate-500', 'dark:text-slate-400');
    }
}

// Function to copy text to clipboard
function copyToClipboard(text, btnId) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">check</span>';
        btn.classList.add('text-green-500');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('text-green-500');
        }, 2000);
    }).catch(err => {
        console.error('Error al copiar: ', err);
        alert('Error al copiar al portapapeles');
    });
}

function sendPaymentWhatsApp(paymentId, clientId, serviceId, sendVia = 'whatsapp') {
    // Show a loading indicator or similar if needed
    const params = new URLSearchParams();
    params.append('client_id', clientId);
    if (serviceId) params.append('service_id', serviceId);
    if (paymentId) params.append('payment_id', paymentId);
    params.append('send_via', sendVia);
    params.append('invoice_type', paymentId || serviceId ? 'service' : 'all');
    params.append('include_paid', '1');
    params.append('include_pending', '1');
    
    // Disable any interaction while sending
    console.log('Sending invoice via ' + sendVia + '...', params.toString());
    
    fetch('send_invoice.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Recibo enviado exitosamente');
        } else {
            alert('Error: ' + (data.message || 'No se pudo enviar el recibo'));
        }
    })
    .catch(error => {
        console.error('Error sending invoice:', error);
        alert('Error de conexión al enviar');
    });
}
</script>

<!-- Document Note Modal -->
<div id="edit-doc-note-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-lg w-full">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Editar Nota del Documento</h3>
                <button onclick="window.document.getElementById('edit-doc-note-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_client_document_note">
            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
            <input type="hidden" name="doc_id" id="note-doc-id">
            <div>
                <label for="doc-note" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nota / Descripción</label>
                <textarea id="doc-note" name="note" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="window.document.getElementById('edit-doc-note-modal').classList.add('hidden')" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition font-medium">
                    Guardar Nota
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditNoteModal(docId, currentNote) {
    const modal = window.document.getElementById('edit-doc-note-modal');
    const docIdInput = window.document.getElementById('note-doc-id');
    const noteTextarea = window.document.getElementById('doc-note');
    
    if (modal && docIdInput && noteTextarea) {
        docIdInput.value = docId;
        noteTextarea.value = currentNote;
        modal.classList.remove('hidden');
    }
}

function deleteDocument(docId) {
    const form = window.document.getElementById('delete-doc-form');
    const docIdInput = window.document.getElementById('delete-doc-id');
    if (form && docIdInput) {
        docIdInput.value = docId;
        form.submit();
    }
}

// Close modals and dropdowns when clicking outside
window.addEventListener('click', function(e) {
    const editModal = window.document.getElementById('edit-doc-note-modal');
    if (e.target === editModal) {
        editModal.classList.add('hidden');
    }
    
    // Close all invoice dropdowns
    if (!e.target.closest('.invoice-dropdown')) {
        document.querySelectorAll('.invoice-dropdown.active').forEach(d => {
            d.classList.remove('active');
        });
    }
});

// Edit Service Functions
function openEditServiceModal(service) {
    const modal = document.getElementById('editServiceModal');
    if (!modal) return;

    // Fill form fields
    document.getElementById('editServiceId').value = service.id;
    document.getElementById('editServiceName').value = service.service_name;
    document.getElementById('editServiceType').value = service.service_type;
    document.getElementById('editServiceStatus').value = service.status;
    document.getElementById('editMonthlyFee').value = service.monthly_fee;
    document.getElementById('editSetupFee').value = service.setup_fee || 0;
    document.getElementById('editBillingCycle').value = service.billing_cycle;
    document.getElementById('editStartDate').value = service.start_date;
    document.getElementById('editRenewalDate').value = service.renewal_date || '';
    document.getElementById('editProgressPercentage').value = service.progress_percentage || 0;
    document.getElementById('editDescription').value = service.description || '';
    document.getElementById('editProjectDescription').value = service.project_description || '';
    document.getElementById('editProjectUrl').value = service.project_url || '';
    document.getElementById('editLegalCoverage').value = service.legal_coverage || '';
    document.getElementById('editRenewalMode').value = service.renewal_mode || 'manual';
    document.getElementById('editBaseServiceId').value = service.base_service_id || '';
    document.getElementById('editPeriodNumber').value = service.period_number || 1;

    // Ads fields
    const isAds = service.is_ads_service == 1 || service.service_type === 'ads';
    document.getElementById('editIsAdsService').checked = isAds;
    document.getElementById('editInitialInvestmentAmount').value = service.initial_investment_amount || 0;

    const adsFields = document.getElementById('editAdsFields');
    if (isAds) {
        adsFields.classList.remove('hidden');
    } else {
        adsFields.classList.add('hidden');
    }

    modal.classList.remove('hidden');
}

function closeEditServiceModal() {
    const modal = document.getElementById('editServiceModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.getElementById('editServiceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditServiceModal();
    }
});

function openConvertQuoteModal(quoteId, quoteNumber, hasAds) {
    document.getElementById('convertQuoteId').value = quoteId;
    document.getElementById('convertQuoteNumber').textContent = quoteNumber;
    
    const adsFields = document.getElementById('adsConvertFields');
    if (hasAds) {
        adsFields.classList.remove('hidden');
        document.getElementById('paymentIncludesInvestment').checked = true;
    } else {
        adsFields.classList.add('hidden');
        document.getElementById('paymentIncludesInvestment').checked = false;
    }
    
    document.getElementById('convertQuoteModal').classList.remove('hidden');
}

function closeConvertQuoteModal() {
    document.getElementById('convertQuoteModal').classList.add('hidden');
}

document.getElementById('convertQuoteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeConvertQuoteModal();
    }
});

// End of scripts

</script>

<?php include 'includes/layout_end.php'; ?>




