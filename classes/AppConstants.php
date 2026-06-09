<?php
/**
 * SOPHEA - Application Constants
 * 
 * Centralized dictionaries for the entire application to ensure consistency
 * and avoid hardcoded strings across multiple files.
 */

class AppConstants {
    
    /**
     * Get all service types
     * @return array Associative array of service types [key => Label]
     */
    public static function getServiceTypes() {
        return [
            'redes_sociales' => 'Manejo de Redes',
            'desarrollo_web' => 'Desarrollo Web',
            'branding' => 'Branding y Diseño',
            'seo_sem' => 'SEO / SEM',
            'campanas_ads' => 'Campañas Ads',
            'fotografia_video' => 'Fotografía / Video',
            'asesoria' => 'Asesoría / Consultoría',
            'email_marketing' => 'Email Marketing',
            'consultoria_legal' => 'Consultoría Legal/Médica',
            'auditoria_datos' => 'Auditoría de Datos',
            'hosting_dominio' => 'Hosting / Dominio',
            'otro' => 'Otro'
        ];
    }

    /**
     * Get label for a specific service type
     */
    public static function getServiceTypeLabel($key) {
        $types = self::getServiceTypes();
        return $types[$key] ?? 'Otro';
    }

    /**
     * Get all quote statuses
     */
    public static function getQuoteStatuses() {
        return [
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada',
            'rejected' => 'Rechazada',
            'expired' => 'Expirada'
        ];
    }
    
    /**
     * Get label for quote status
     */
    public static function getQuoteStatusLabel($status) {
        $statuses = self::getQuoteStatuses();
        return $statuses[$status] ?? 'Desconocido';
    }

    /**
     * Get color classes for quote status
     */
    public static function getQuoteStatusColor($status) {
        switch ($status) {
            case 'pending': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50';
            case 'accepted': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50';
            case 'rejected': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800/50';
            case 'expired': return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
            default: return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
        }
    }

    /**
     * Get all service/project statuses
     */
    public static function getServiceStatuses() {
        return [
            'active' => 'Activo',
            'suspended' => 'Suspendido',
            'cancelled' => 'Cancelado'
        ];
    }

    /**
     * Get label for service status
     */
    public static function getServiceStatusLabel($status) {
        $statuses = self::getServiceStatuses();
        return $statuses[$status] ?? 'Desconocido';
    }

    /**
     * Get color classes for service status
     */
    public static function getServiceStatusColor($status) {
        switch ($status) {
            case 'active': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50';
            case 'suspended': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800/50';
            case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800/50';
            default: return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700';
        }
    }

    /**
     * Get currencies
     */
    public static function getCurrencies() {
        return ['MXN', 'USD'];
    }

    /**
     * Get expense categories / types
     */
    public static function getExpenseTypes() {
        return [
            'hosting' => 'Hosting',
            'domain' => 'Dominio',
            'platform' => 'Plataforma',
            'software' => 'Software',
            'salary' => 'Sueldo',
            'freelancer' => 'Freelancer',
            'marketing' => 'Marketing',
            'ads_facebook' => 'Facebook Ads',
            'ads_google' => 'Google Ads',
            'ads_instagram' => 'Instagram Ads',
            'ads_tiktok' => 'TikTok Ads',
            'ads_linkedin' => 'LinkedIn Ads',
            'ads_other' => 'Otros Ads',
            'office' => 'Oficina',
            'utilities' => 'Servicios',
            'other' => 'Otro'
        ];
    }
    
    /**
     * Get reimbursement status labels
     */
    public static function getReimbursementStatusLabels() {
        return [
            'not_required' => 'No requerido',
            'pending' => 'Pendiente',
            'billed' => 'Facturado',
            'paid' => 'Pagado'
        ];
    }
    
    /**
     * Get billing cycle labels
     */
    public static function getBillingCycleLabels() {
        return [
            'one_time' => 'Una vez',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual'
        ];
    }
    
    /**
     * Get payment methods
     */
    public static function getPaymentMethods() {
        return [
            'transfer' => 'Transferencia',
            'card' => 'Tarjeta',
            'cash' => 'Efectivo',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'other' => 'Otro'
        ];
    }

    /**
     * Get payment statuses
     */
    public static function getPaymentStatuses() {
        return [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'overdue' => 'Vencido',
            'cancelled' => 'Cancelado'
        ];
    }
}
