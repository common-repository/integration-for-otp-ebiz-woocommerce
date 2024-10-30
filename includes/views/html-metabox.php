<?php if(!$this->get_option('api_key')): ?>
  <p style="text-align: center;"><?php esc_html_e('A számlakészítéshez meg kell adnod az API kulcsot a bővítmény beállításokban!','wc-ebiz'); ?></p>
<?php else: ?>
  <div id="wc-ebiz-messages"></div>
  <?php if(get_post_meta($post->ID,'_wc_ebiz_own',true)): ?>
    <div style="text-align:center;" id="ebiz_already_div">
      <?php $note = get_post_meta($post->ID,'_wc_ebiz_own',true); ?>
      <p><?php esc_html_e('A számlakészítés ki lett kapcsolva, mert: ','wc-ebiz'); ?><strong><?php echo $note; ?></strong><br>
      <a id="wc_ebiz_already_back" href="#" data-nonce="<?php echo wp_create_nonce( "wc_already_invoice" ); ?>" data-order="<?php echo $post->ID; ?>"><?php esc_html_e('Visszakapcsolás','wc-ebiz'); ?></a>
      </p>
    </div>
  <?php endif; ?>
  <?php if(get_post_meta($post->ID,'_wc_ebiz_dijbekero_pdf',true)): ?>
  <p>Díjbekérő <span class="alignright"><?php echo get_post_meta($post->ID,'_wc_ebiz_dijbekero',true); ?> - <a href="<?php echo esc_attr($this->generate_download_link($post->ID,'dijbekero')); ?>">Letöltés</a></span></p>
  <hr/>
  <?php endif; ?>

  <?php if($this->is_invoice_generated($post->ID) && !get_post_meta($post->ID,'_wc_ebiz_own',true)): ?>
    <div style="text-align:center;" id="wc-ebiz-generate-button">
        <div id="wc-ebiz-generated-data">
          <p><?php esc_html_e('Számla sikeresen létrehozva és elküldve a vásárlónak emailben.','wc-ebiz'); ?></p>
          <p><?php esc_html_e('A számla sorszáma:','wc-ebiz'); ?> <strong><?php echo get_post_meta($post->ID,'_wc_ebiz',true); ?></strong></p>
          <p><?php esc_html_e('A számla azonosítója:','wc-ebiz'); ?> <strong><?php echo get_post_meta($post->ID,'_wc_ebiz_id',true); ?></strong></p>
          <p><a href="<?php echo esc_attr($this->generate_download_link($post->ID)); ?>" class="button button-primary" target="_blank"><?php esc_html_e('Számla megtekintése','wc-ebiz'); ?></a></p>
          <?php if(!get_post_meta($post->ID,'_wc_ebiz_jovairas',true)): ?>
            <p style="display:none"><a href="#" id="wc_ebiz_generate_complete" data-order="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce( "wc_generate_invoice" ); ?>" target="_blank"><?php _e('Teljesítve','wc-ebiz'); ?></a></p>
          <?php else: ?>
            <p><?php _e('Jóváírás rögzítve','wc-ebiz'); ?>: <?php echo date('Y-m-d',get_post_meta($post->ID,'_wc_ebiz_jovairas',true)); ?></p>
          <?php endif; ?>
        </div>
        <p class="plugins"><a href="#" id="wc_ebiz_generate_sztorno" data-order="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce( "wc_generate_invoice" ); ?>" class="delete"><?php _e('Sztornózás','wc-ebiz'); ?></a></p>
    </div>
  <?php else: ?>
    <div style="text-align:center;<?php if(get_post_meta($post->ID,'_wc_ebiz_own',true)): ?>display:none;<?php endif; ?>" id="wc-ebiz-generate-button">
        <div class="wc_ebiz_options_buttons">
          <a href="#" id="wc_ebiz_options"><?php esc_html_e('Opciók','wc-ebiz'); ?></a>
          <a href="#" id="wc_ebiz_generate" data-order="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce( "wc_generate_invoice" ); ?>" class="button button-primary" target="_blank">
            <?php esc_html_e('Számlakészítés','wc-ebiz'); ?>
          </a>
        </div>
        <div id="wc_ebiz_options_form" style="display:none;">
          <div class="fields">
            <h4><?php esc_html_e('Megjegyzés','wc-ebiz'); ?></h4>
            <input type="text" id="wc_ebiz_invoice_note" value="<?php echo $this->get_option('note'); ?>" />
            <h4><?php esc_html_e('Fizetési határidő(nap)','wc-ebiz'); ?></h4>
            <input type="number" id="wc_ebiz_invoice_deadline" value="<?php echo $this->get_payment_method_deadline($order->get_payment_method()); ?>" />
            <h4><?php esc_html_e('Teljesítés dátum','wc-ebiz'); ?></h4>
            <input type="text" class="date-picker" id="wc_ebiz_invoice_completed" maxlength="10" value="<?php echo date('Y-m-d'); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
            <div class="wc_ebiz_invoice_types">
              <h4><?php esc_html_e('Számla típusa','wc-ebiz'); ?></h4>
              <label for="wc_ebiz_invoice_normal">
                <input type="radio" name="invoice_extra_type" id="wc_ebiz_invoice_normal" value="1" checked="checked" />
                <span><?php esc_html_e('Végszámla','wc-ebiz'); ?></span>
              </label>
              <label for="wc_ebiz_invoice_request">
                <input type="radio" name="invoice_extra_type" id="wc_ebiz_invoice_request" value="1" />
                <span><?php esc_html_e('Díjbekérő számla','wc-ebiz'); ?></span>
              </label>
            </div>
          </div>
          <a id="wc_ebiz_already" href="#" data-nonce="<?php echo wp_create_nonce( "wc_already_invoice" ); ?>" data-order="<?php echo $post->ID; ?>"><?php esc_html_e('Számlakészítés kikapcsolása','wc-ebiz'); ?></a>
        </div>
        <?php if($this->get_option('auto') == 'yes'): ?>
          <p><small><?php esc_html_e('A számla automatikusan elkészül és el lesz küldve a vásárlónak, ha a rendelés állapota befejezettre lesz átállítva.','wc-ebiz'); ?></small></p>
        <?php endif; ?>
    </div>

    <?php if(get_post_meta($post->ID,'_wc_ebiz_sztorno',true)): ?>
      <p>Sztornó számla: <span class="alignright"><?php echo esc_html(get_post_meta($post->ID,'_wc_ebiz_sztorno',true)); ?> - <a href="<?php echo esc_attr($this->generate_download_link($post->ID,'sztorno')); ?>" target="_blank">Letöltés</a></span></p>
    <?php endif; ?>

  <?php endif; ?>
<?php endif; ?>
