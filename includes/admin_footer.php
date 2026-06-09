<?php
/**
 * SOPHEA - Admin Panel Footer
 * 
 * Footer component for admin panel
 */
?>

<!-- Scripts -->
<!-- Chart.js for WhatsApp Marketing charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Toggle dark mode (if needed)
    // You can add dark mode toggle functionality here
    
    // Auto-update overdue payments status
    function updateOverduePayments() {
        // This could be called periodically to update payment statuses
        // Implementation depends on your needs
    }
    
    // Initialize tooltips, modals, etc.
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Sidebar Toggle
        initMobileSidebar();
    });
    
    // Mobile Sidebar Functionality
    function initMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        
        if (!sidebar || !overlay) return;
        
        // Function to open sidebar
        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
        }
        
        // Function to close sidebar
        function closeSidebar() {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = ''; // Restore body scroll
        }
        
        // Toggle button click
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                openSidebar();
            });
        }
        
        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeSidebar();
            });
        }
        
        // Overlay click to close
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
        
        // Close sidebar when clicking on a link (mobile only)
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Only close on mobile (screen width < 768px)
                if (window.innerWidth < 768) {
                    closeSidebar();
                }
            });
        });
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                closeSidebar();
            }
        });
        
        // Handle window resize - close sidebar if resizing to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 768) {
                    // On desktop, ensure sidebar is visible
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                } else {
                    // On mobile, ensure sidebar is hidden by default
                    if (!sidebar.classList.contains('-translate-x-full')) {
                        closeSidebar();
                    }
                }
            }, 100);
        });
    }
    
    // Auto-update new leads count badge (works on all pages)
    function updateLeadsBadge() {
        fetch('api_get_new_leads_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('leadsBadge');
                    const count = data.count || 0;
                    
                    if (count > 0) {
                        if (badge) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.style.display = 'flex';
                        } else {
                            // Create badge if it doesn't exist
                            const leadsLink = document.querySelector('a[href="admin.php"]');
                            if (leadsLink && !leadsLink.querySelector('#leadsBadge')) {
                                const newBadge = document.createElement('span');
                                newBadge.id = 'leadsBadge';
                                newBadge.className = 'bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5';
                                newBadge.textContent = count > 99 ? '99+' : count;
                                leadsLink.appendChild(newBadge);
                            }
                        }
                    } else {
                        if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error updating leads badge:', error);
            });
    }
    
    // Update badge on page load and every 5 minutes
    document.addEventListener('DOMContentLoaded', function() {
        updateLeadsBadge();
        setInterval(updateLeadsBadge, 300000); // Update every 5 minutes (300000 ms)
    });
</script>

</body>
</html>

