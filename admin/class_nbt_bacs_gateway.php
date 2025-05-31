<?php
    class WC_Gateway_BACS_NBT extends WC_Gateway_BACS {

        public function __construct() {
            parent::__construct();

            // Add custom fields for bank account details
            add_filter('woocommerce_bacs_account_fields', [$this, 'custom_bacs_fields']);
        }

        public function save_account_details() {

        $accounts = array();

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification already handled in WC_Admin_Settings::save()
        if ( isset( $_POST['bacs_account_name'] ) && isset( $_POST['bacs_account_number'] ) && isset( $_POST['bacs_bank_name'] )
             && isset( $_POST['bacs_sort_code'] ) && isset( $_POST['bacs_iban'] ) && isset( $_POST['bacs_bic'] ) ) {

            $location   = wc_clean( wp_unslash( $_POST['location'] ) );
            $account_names   = wc_clean( wp_unslash( $_POST['bacs_account_name'] ) );
            $account_numbers = wc_clean( wp_unslash( $_POST['bacs_account_number'] ) );
            $bank_names      = wc_clean( wp_unslash( $_POST['bacs_bank_name'] ) );
            $sort_codes      = wc_clean( wp_unslash( $_POST['bacs_sort_code'] ) );
            $ibans           = wc_clean( wp_unslash( $_POST['bacs_iban'] ) );
            $bics            = wc_clean( wp_unslash( $_POST['bacs_bic'] ) );

            foreach ( $account_names as $i => $name ) {
                if ( ! isset( $account_names[ $i ] ) ) {
                    continue;
                }

                $accounts[] = array(
                    'account_name'   => $account_names[ $i ],
                    'location'       => $location[ $i ],   
                    'account_number' => $account_numbers[ $i ],
                    'bank_name'      => $bank_names[ $i ],
                    'sort_code'      => $sort_codes[ $i ],
                    'iban'           => $ibans[ $i ],
                    'bic'            => $bics[ $i ],
                );
            }
        }
        // phpcs:enable
       
        do_action( 'woocommerce_update_option', array( 'id' => 'woocommerce_bacs_accounts' ) );
        update_option( 'woocommerce_bacs_accounts', $accounts );
    }

        public function admin_options() {
            echo '<h2>' . __('Bank Transfer', 'woocommerce') . '</h2>';
            echo '<p>' . __('Allows payments via bank transfer.', 'woocommerce') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }
        public function generate_account_details_html() {
            ob_start();

            $country = WC()->countries->get_base_country();
            $locale  = $this->get_country_locale();
            $nbtlocations = get_option('nbt_locations', []);
            $location_options = '';
            $sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'woocommerce' );
            // Prepare options for locations
            if (!empty($nbtlocations)) {
                foreach ($nbtlocations as $location) {
                   
                    $location_options .= '<option >' . esc_html($location['location']) . '</option>';
                }
            }

            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label>
                        <?php esc_html_e('Account details:', 'woocommerce'); ?>
                        <?php echo wp_kses_post(wc_help_tip(__('These account details will be displayed within the order thank you page and confirmation email.', 'woocommerce'))); ?>
                    </label>
                </th>
                <td class="forminp" id="bacs_accounts">
                    <div class="wc_input_table_wrapper">
                        <table class="widefat wc_input_table sortable" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="sort">&nbsp;</th>
                                    <th><?php esc_html_e('Location', 'woocommerce'); ?></th>
                                    <th><?php esc_html_e('Account name', 'woocommerce'); ?></th>
                                    <th><?php esc_html_e('Account number', 'woocommerce'); ?></th>
                                    <th><?php esc_html_e('Bank name', 'woocommerce'); ?></th>
                                    <th><?php echo esc_html($sortcode); ?></th>
                                    <th><?php esc_html_e('IBAN', 'woocommerce'); ?></th>
                                    <th><?php esc_html_e('BIC / Swift', 'woocommerce'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="accounts">
                                <?php
                                $i = -1;
                                if ($this->account_details) {
                                    foreach ($this->account_details as $account) {
                                        $location_opts = '';
                                        if (!empty($nbtlocations)) {
                                            foreach ($nbtlocations as $location) {
                                                $selected = $location['location'] == $account['location'] ? 'selected' : '';
                                                $location_opts .= '<option '.$selected.'>' . esc_html($location['location']) . '</option>';
                                            }
                                        }
                                        $i++;
                                        echo '<tr class="account">
                                            <td class="sort"></td>
                                            <td><select name="location[' . esc_attr($i) . ']">' . $location_opts . '</select></td>
                                            <td><input type="text" value="' . esc_attr(wp_unslash($account['account_name'])) . '" name="bacs_account_name[' . esc_attr($i) . ']" /></td>
                                            <td><input type="text" value="' . esc_attr($account['account_number']) . '" name="bacs_account_number[' . esc_attr($i) . ']" /></td>
                                            <td><input type="text" value="' . esc_attr(wp_unslash($account['bank_name'])) . '" name="bacs_bank_name[' . esc_attr($i) . ']" /></td>
                                            <td><input type="text" value="' . esc_attr($account['sort_code']) . '" name="bacs_sort_code[' . esc_attr($i) . ']" /></td>
                                            <td><input type="text" value="' . esc_attr($account['iban']) . '" name="bacs_iban[' . esc_attr($i) . ']" /></td>
                                            <td><input type="text" value="' . esc_attr($account['bic']) . '" name="bacs_bic[' . esc_attr($i) . ']" /></td>
                                        </tr>';
                                    }
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7"><a href="#" class="add button"><?php esc_html_e('+ Add account', 'woocommerce'); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e('Remove selected account(s)', 'woocommerce'); ?></a></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <script type="text/javascript">
                        jQuery(function() {
                            var locationOptions = '<?php echo addslashes($location_options); ?>'; // Pass location options to JS
                            jQuery('#bacs_accounts').on('click', 'a.add', function() {
                                var size = jQuery('#bacs_accounts').find('tbody .account').length;

                                jQuery('<tr class="account">\
                                        <td class="sort"></td>\
                                        <td><select name="location[' + size + ']">' + locationOptions + '</select></td>\
                                        <td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
                                        <td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
                                        <td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
                                        <td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
                                        <td><input type="text" name="bacs_iban[' + size + ']" /></td>\
                                        <td><input type="text" name="bacs_bic[' + size + ']" /></td>\
                                    </tr>').appendTo('#bacs_accounts table tbody');

                                return false;
                            });
                        });
                    </script>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }
        
        /**
     * Get bank details and place into a list format.
     *
     * @param int $order_id Order ID.
     */
   
        
}