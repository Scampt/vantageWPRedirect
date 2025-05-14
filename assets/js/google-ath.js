jQuery(document).ready(function($) {
    // Conexión con Google
    $('#vantagewp-connect-google').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Conectando...');
        
        $.ajax({
            url: vantagewp_ajax_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vantagewp_google_auth',
                nonce: vantagewp_google_data.nonce
            },
            success: function(response) {
                if (response.success && response.data.auth_url) {
                    window.location.href = response.data.auth_url;
                } else {
                    alert('Error: ' + (response.data.message || 'Error desconocido'));
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-google"></span> Conectar con Google');
                }
            },
            error: function() {
                alert('Error de conexión');
                $button.prop('disabled', false).html('<span class="dashicons dashicons-google"></span> Conectar con Google');
            }
        });
    });

    // Desconexión de Google
    $('#vantagewp-revoke-google').on('click', function() {
        if (confirm('¿Estás seguro de que deseas desconectar tu cuenta de Google?')) {
            // AJAX para desconectar (implementar en el backend)
        }
    });
});