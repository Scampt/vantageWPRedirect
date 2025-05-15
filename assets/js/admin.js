jQuery(document).ready(function($) {
    function loadSubscriptions() {
        $.ajax({
            url: vantagewp_ajax_data.ajaxurl, // Usa el objeto correcto
            type: 'POST',
            data: {
                action: 'vantage_get_subscriptions',
                security: vantagewp_ajax_data.nonce,
                user_id: vantagewp_ajax_data.user_id
            },
            beforeSend: function() {
                $('#vantage-subscriptions-container').html('<p>Cargando suscripciones...</p>');
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#vantage-subscriptions-container').html(response.data);
                } else {
                    $('#vantage-subscriptions-container').html('<p>Error al cargar suscripciones</p>');
                    console.error('Error:', response);
                }
            },
            error: function(xhr, status, error) {
                $('#vantage-subscriptions-container').html('<p>Error de conexión</p>');
                console.error('AJAX Error:', error);
            }
        });
    }

    // Cargar al inicio
    loadSubscriptions();
});