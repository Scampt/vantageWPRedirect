<div class="vantagewp-section">
    <h2>
        <span class="dashicons dashicons-admin-users"></span> Datos Personales 
        <button id="vantagewp-edit-profile" class="button button-small">
            <span class="dashicons dashicons-edit"></span> Editar
        </button>
    </h2>
    
    <form id="vantagewp-profile-form" method="post" style="display:none;">
        <?php wp_nonce_field('vantagewp_update_profile', 'vantagewp_nonce'); ?>
        
        <table class="vantagewp-data-table">
            <tr><th>Nombre de usuario:</th><td><?php echo esc_html($user_data->user_login); ?></td></tr>
            <tr><th>Nombre:</th><td><input type="text" name="first_name" value="<?php echo esc_attr($user_data->first_name); ?>" class="regular-text"></td></tr>
            <tr><th>Apellido:</th><td><input type="text" name="last_name" value="<?php echo esc_attr($user_data->last_name); ?>" class="regular-text"></td></tr>
            <tr><th>Email:</th><td><input type="email" name="user_email" value="<?php echo esc_attr($user_data->user_email); ?>" class="regular-text"></td></tr>
        </table>
        
        <div class="vantagewp-form-actions">
            <button type="submit" class="button button-primary">Guardar cambios</button>
            <button type="button" id="vantagewp-cancel-edit" class="button">Cancelar</button>
        </div>
    </form>
    
    <div id="vantagewp-profile-view">
        <table class="vantagewp-data-table">
            <tr><th>Nombre:</th><td><?php echo esc_html($user_data->first_name); ?></td></tr>
            <tr><th>Apellido:</th><td><?php echo esc_html($user_data->last_name); ?></td></tr>
            <tr><th>Nombre de usuario:</th><td><?php echo esc_html($user_data->user_login); ?></td></tr>
            <tr><th>Email:</th><td><?php echo esc_html($user_data->user_email); ?></td></tr>
        </table>
    </div>
</div>