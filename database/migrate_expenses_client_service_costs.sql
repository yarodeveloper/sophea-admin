-- =====================================================
-- SOPHEA - Migración: Costos de Servicios de Clientes
-- Opción 3: Sistema Híbrido - Extender expenses
-- =====================================================
-- Este script agrega campos a la tabla expenses para
-- asociar gastos con clientes y servicios, permitiendo
-- rastrear costos de servicios como Facebook Ads, etc.
-- =====================================================

USE sophea_db;

-- Agregar nuevos campos a la tabla expenses
ALTER TABLE expenses 
ADD COLUMN client_id INT NULL COMMENT 'ID del cliente (si es costo de servicio)',
ADD COLUMN service_id INT NULL COMMENT 'ID del servicio/proyecto (si es costo de servicio)',
ADD COLUMN is_client_service_cost BOOLEAN DEFAULT FALSE COMMENT 'Si es costo asociado a servicio de cliente',
ADD COLUMN campaign_id VARCHAR(255) NULL COMMENT 'ID de campaña en plataforma externa (Facebook, Google, etc.)',
ADD COLUMN billing_period_start DATE NULL COMMENT 'Inicio del período facturado',
ADD COLUMN billing_period_end DATE NULL COMMENT 'Fin del período facturado',
ADD COLUMN reimbursement_status ENUM('not_required', 'pending', 'billed', 'paid') DEFAULT 'not_required' COMMENT 'Estado de reembolso al cliente';

-- Agregar índices para mejorar rendimiento
ALTER TABLE expenses 
ADD INDEX idx_client_id (client_id),
ADD INDEX idx_service_id (service_id),
ADD INDEX idx_is_client_service_cost (is_client_service_cost),
ADD INDEX idx_campaign_id (campaign_id);

-- Agregar foreign keys
ALTER TABLE expenses 
ADD CONSTRAINT fk_expenses_client 
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_expenses_service 
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;

-- Extender expense_type para incluir tipos de Ads
ALTER TABLE expenses 
MODIFY COLUMN expense_type ENUM(
    'hosting',
    'domain',
    'platform',
    'software',
    'salary',
    'freelancer',
    'marketing',
    'ads_facebook',
    'ads_google',
    'ads_instagram',
    'ads_tiktok',
    'ads_linkedin',
    'ads_other',
    'office',
    'utilities',
    'other'
) NOT NULL;

-- Comentarios en la tabla
ALTER TABLE expenses 
COMMENT = 'Gastos operativos y costos de servicios de clientes';

-- =====================================================
-- Notas:
-- - Si is_client_service_cost = TRUE, entonces client_id y service_id deben estar presentes
-- - Los gastos operativos tienen is_client_service_cost = FALSE y client_id/service_id = NULL
-- - Los costos de servicios tienen is_client_service_cost = TRUE y client_id/service_id asignados
-- =====================================================

