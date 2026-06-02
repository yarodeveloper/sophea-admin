-- =====================================================
-- SOPHEA - Actualizar ENUM de platform en project_transactions
-- =====================================================
-- Este script actualiza el ENUM de platform para incluir 'whatsapp'
-- y remover 'instagram' (ya que ahora se usa META/facebook)

USE sophea_db;

-- Modificar el ENUM de platform
ALTER TABLE project_transactions 
MODIFY COLUMN platform ENUM('facebook', 'whatsapp', 'google', 'tiktok', 'linkedin', 'other') NULL;

