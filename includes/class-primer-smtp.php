<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PrimerSMTP {

	public $opts;
	protected static $instance = null;

	public function __construct() {
		$this->opts        = get_option( 'primer_emails' );
		$this->opts        = ! is_array( $this->opts ) ? array() : $this->opts;
		require_once 'class-primer-smtp-utils.php';
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_mail_failed', array( $this, 'wp_mail_failed' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function wp_mail( $args ) {
		return $args;
	}

	public function wp_mail_failed( $wp_error ) {
		if ( ! empty( $wp_error->errors ) && ! empty( $wp_error->errors['wp_mail_failed'] ) && is_array( $wp_error->errors['wp_mail_failed'] ) ) {
			printf('<div class="primer_popup popup_error"><h3>' . esc_html__('*** ' . implode( ' | ', $wp_error->errors['wp_mail_failed'] ) . " ***\r\n", 'primer') . '</h3></div>' );
		}
	}

	public function init_smtp( &$phpmailer ) {
		//check if SMTP credentials have been configured.
		if ( ! $this->credentials_configured() ) {
			return;
		}

		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->IsSMTP();

		$from_email = $this->opts['from_email_field'];
		$from_name  = get_bloginfo( 'name' );
		$phpmailer->SetFrom( $phpmailer->From, $phpmailer->FromName );

		if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
			$phpmailer->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
		}

		/* Set the other options */
		$phpmailer->Host = $this->opts['smtp_settings']['smtp_server'];
		$phpmailer->Port = $this->opts['smtp_settings']['port'];


		/* If we're using smtp auth, set the username & password */
		if ( 'yes' === $this->opts['smtp_settings']['authentication'] ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $this->opts['smtp_settings']['username'];
			$phpmailer->Password = $this->get_password();
		}
		//PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate.
		$phpmailer->SMTPAutoTLS = false;
		//set reasonable timeout
		$phpmailer->Timeout = 10;
	}

	public function primer_mail_sender($send_to_mail,$from_name_email, $mail_subject, $mail_message, $attachments) {
		$response = array();
		//check if SMTP credentials have been configured.
		if ( ! $this->credentials_configured() ) {
			return;
		}

		global $wp_version;

		if ( version_compare( $wp_version, '5.4.99' ) > 0 ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$mail = new PHPMailer( true );
		} else {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mail = new \PHPMailer( true );
		}

		try {
			$mail->IsSMTP();

			$charset       = get_bloginfo( 'charset' );
			$mail->CharSet = $charset;
			// send plain text test email
			$mail->ContentType = 'text/html';
			$mail->IsHTML( true );


			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->opts['smtp_settings']['authentication'] ) {
				$mail->SMTPAuth = true;
				if (!empty($this->opts['smtp_settings']['username'])) {
					$mail->Username = $this->opts['smtp_settings']['username'];
				}
				$mail->Password = $this->get_password();
			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
				$mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
			} else {
				$mail->SMTPSecure = 'ssl';
			}

			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = true;

			/* Set the other options */
			if (!empty($this->opts['smtp_settings']['smtp_server'])) {
				$mail->Host = $this->opts['smtp_settings']['smtp_server'];
			}

			if (!empty($this->opts['smtp_settings']['port'])) {
				$mail->Port = $this->opts['smtp_settings']['port'];
			}
			$request_port = isset($_POST['primer_smtp_port']) ? sanitize_text_field($_POST['primer_smtp_port']) : '';
			if (!empty($this->opts['smtp_settings']['port']) && !empty($request_host)) {
				$mail->Port = $request_port;
			}


			$send_from_mail = $this->opts['from_email_field'];
            if($from_name_email != ''){
                $from_name = $from_name_email;
            }else {
                $from_name = get_bloginfo('name');
            }
			$mail->SetFrom( $send_from_mail, $from_name );

			$mail->Subject = $mail_subject;
			$mail->Body    = $mail_message;
			$mail->AddAddress( $send_to_mail, 'User Name' );

			if (!empty($attachments)) {
				$mail->addAttachment($attachments);
			}

			global $debug_msg;
			$debug_msg         = '';
			$mail->Debugoutput = function ( $str, $level ) {
				global $debug_msg;
				$debug_msg .= $str;
			};
			$mail->SMTPDebug   = 2;
			//set reasonable timeout
			$mail->Timeout = 10;

			/* Send mail and return result */
			$mail->Send();

			$mail->ClearAddresses();
			$mail->ClearAllRecipients();

		} catch ( \Exception $e ) {
			$response['error'] = $mail->ErrorInfo;
		} catch ( \Throwable $e ) {
			$response['error'] = $mail->ErrorInfo;
		}
		$response['debug_log'] = $debug_msg;

		if (!empty($response['error'])) {
			//printf('<div class="primer_popup popup_error"><h3>' . $response['error'] . '</h3></div>');
		}
		else {
			//printf('<div class="primer_popup popup_success"><h3>' . esc_html__('Test email sent successfully', 'primer') . '</h3></div>');
		}

		return $response;
	}

	public function test_mail( $to_email, $subject, $message ) {
		$ret = array();
		if ( ! $this->credentials_configured() &&  $_POST['smtp_type'] == 'other_smtp') {
			return false;
		}

		global $wp_version;
        global $debug_msg;

		if ( version_compare( $wp_version, '5.4.99' ) > 0 ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$mail = new PHPMailer( true );
		} else {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mail = new \PHPMailer( true );
		}

		try {
            if($_POST['smtp_type'] == 'other_smtp') {
                $charset = get_bloginfo('charset');
                $mail->CharSet = $charset;

                $from_name = get_bloginfo('name');
                $from_email_request = isset($_POST['primer_from_email']) ? sanitize_email($_POST['primer_from_email']) : '';
                if(empty($from_email_request)){
                    $from_email_request = get_option( 'admin_email' );
                }
                if (!empty($this->opts['from_email_field'])) {
                    $from_email = $this->opts['from_email_field'];
                } else {
                    if (!empty($from_email_request)) {
                        $from_email = $from_email_request;
                    }
                }

                $mail->IsSMTP();

                // send plain text test email
                $mail->ContentType = 'text/html';
                $mail->IsHTML(true);

                $mail->SMTPAuth = true;
                /* If using smtp auth, set the username & password */
                if ('yes' === $this->opts['smtp_settings']['authentication'] || 'yes' === sanitize_text_field($_POST['primer_smtp_authentication'])) {

                    $request_username = isset($_POST['primer_smtp_username']) ? sanitize_text_field($_POST['primer_smtp_username']) : '';
                    if (!empty($this->opts['smtp_settings']['username'])) {
                        $mail->Username = $this->opts['smtp_settings']['username'];
                    } else {
                        if (!empty($request_username)) {
                            $mail->Username = $request_username;
                        }
                    }

                    $mail->Password = $this->get_password();

                }

                /* Set the SMTPSecure value, if set to none, leave this blank */
                if ('none' !== $this->opts['smtp_settings']['type_encryption']) {
                    $mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
                } else {
                    $mail->SMTPSecure = 'ssl';
                }
                /* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
                $mail->SMTPAutoTLS = true;
                /* Set the other options */
                if (!empty($this->opts['smtp_settings']['smtp_server'])) {
                    $mail->Host = $this->opts['smtp_settings']['smtp_server'];
                }
                $request_host = isset($_POST['primer_smtp_host']) ? sanitize_text_field($_POST['primer_smtp_host']) : '';
                if (!empty($this->opts['smtp_settings']['smtp_server']) && !empty($request_host)) {
                    $mail->Host = $request_host;
                }

                if (!empty($this->opts['smtp_settings']['port'])) {
                    $mail->Port = $this->opts['smtp_settings']['port'];
                }
                $request_port = isset($_POST['primer_smtp_port']) ? sanitize_text_field($_POST['primer_smtp_port']) : '';
                if (!empty($this->opts['smtp_settings']['port']) && !empty($request_host)) {
                    $mail->Port = $request_port;
                }
                //Add reply-to if set in settings.
                if (!empty($this->opts['reply_to_email'])) {
                    $mail->AddReplyTo($this->opts['reply_to_email'], $from_name);
                }

                $mail->SetFrom($from_email, $from_name);
                //This should set Return-Path header for servers that are not properly handling it, but needs testing first
                //$mail->Sender		 = $mail->From;
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AddAddress($to_email);
                $debug_msg = '';
                $mail->Debugoutput = function ($str, $level) {
                    global $debug_msg;
                    $debug_msg .= $str;
                };
                $mail->SMTPDebug = 2;
                //set reasonable timeout
                $mail->Timeout = 10;
                $mailResultSMTP = '';
            }
            if($_POST['smtp_type'] == 'other_smtp') {
                /* Send mail and return result */
                $mail->Send();
                $mail->ClearAddresses();
                $mail->ClearAllRecipients();
            }else{
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $primer_smtp_subject = 'Test Email';
                $primer_smtp_message = 'Test email sent successfully';
                $mailResultSMTP = wp_mail(get_option( 'admin_email' ),$primer_smtp_subject,$primer_smtp_message,$headers);
            }


		} catch ( \Exception $e ) {
			$ret['error'] = $mail->ErrorInfo;
		} catch ( \Throwable $e ) {
			$ret['error'] = $mail->ErrorInfo;
		}
		$ret['debug_log'] = $debug_msg;
        if($_POST['smtp_type'] == 'other_smtp') {
            if (!empty($ret['error'])) {
                printf('<div class="primer_popup popup_error"><h3>' . $ret['error'] . '</h3></div>');
            } else {
                printf('<div class="primer_popup popup_success"><h3>' . esc_html__('Test email sent successfully', 'primer') . '</h3></div>');
            }
        }else{
            if($mailResultSMTP){
                printf('<div class="primer_popup popup_success"><h3>' . esc_html__('Test email sent successfully', 'primer') . '</h3></div>');
            }else{
                global $ts_mail_errors, $phpmailer;
                $ret=array();
                if ( ! isset($ts_mail_errors) )
                    $ts_mail_errors = array();
                if ( isset($phpmailer) )
                    $ts_mail_errors[] = $phpmailer->ErrorInfo;
                $ret['error'] = $phpmailer->ErrorInfo;
                printf('<div class="primer_popup popup_error"><h3>' . json_encode($ts_mail_errors) . '</h3></div>');
            }
        }

		return $ret;
	}

    /**
     * Displays admin notices based on various conditions like license expiration, total receipts, activation updates, etc.
     */
	public function admin_notices() {
        $mydata_options = get_option( 'primer_mydata' );
        $primer_license = get_option( 'primer_licenses' );
        $primer_smtp_options = get_option('primer_emails');
        $now = time(); // or your date as well
        if(!empty($primer_license['endDate'])){
        $your_date = strtotime($primer_license['endDate']);
        $plugin_edition = $primer_license['wpEdition'];
        $datediff = $your_date - $now;
        $datedifference = round($datediff / (60 * 60 * 24));
        if($datedifference <= 10 && $plugin_edition != 'PRwpluginDM'){
        ?><div class="error" id="update-activation-notice">
        <p>
            <?php
            printf( __('Your Primer MyData subscription ends in approximately %s days. Go to <a target="_blank" href="https://primer.gr"> primer.gr</a> and renew your subscription in my accounts page.', 'primer'), $datedifference);
            ?>

        </p>
        </div>
        <?php }
        }
        $total_receipts = array_sum((array) wp_count_posts('primer_receipt'));

        if ($total_receipts >= 50 && (!get_option('primer_do_not_show_notice') )) {
            ?>
            <div class="notice notice-success" id="update-activation-notice" style="display: flex; align-items: center;">
                <p style="flex-grow: 1;">
                    <?php
                    printf(__('It looks like you issued a lot of orders lately. Please leave us a 5-star recommendation in WordPress <a target="_blank" href="https://wordpress.org/support/plugin/primer-mydata/reviews/#new-post"> here.</a>', 'primer'));
                    ?>
                </p>
                <?php
                if (isset($_GET['do_not_show_again'])) {
                    update_option('primer_do_not_show_notice', 'true');
                    wp_redirect(remove_query_arg('do_not_show_again'));
                } else {
                    ?>
                    <a href="<?php echo add_query_arg('do_not_show_again', '1'); ?>" style="margin-left: 10px;">
                        <?php _e('Do Not Show Again', 'primer'); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php
        }
        if(is_array($mydata_options) && $mydata_options['activation_update'] != 5) {
        ?><div class="error" id="update-activation-notice">
        <p>
            <?php
                printf(__('Please re-activate your product in order the plugin to be fully updated.', 'primer'));
            ?>
        </p>
        </div>
        <?php }
        if(!empty($mydata_options['check_0_remaining'])){
        if($mydata_options['check_0_remaining'] == 1) {
            ?><div class="error" id="update-activation-notice">
            <p>
                <?php
                printf(__('You have no other monthly invoices left.Please go to MyData settings and press "Get Remaining" button if you are sure that the month is passed and you have remaining invoices in order to continue issuing orders.', 'primer'));
                ?>
            </p>
            </div>
        <?php }
        }
		if (!$this->credentials_configured() && $primer_smtp_options['smtp_type'] === 'other_smtp') {
			$settings_url = admin_url() . 'admin.php?page=primer_settings&tab=emails'; ?>
			<div class="error">
				<p>
					<?php
					printf( __( 'Please configure your SMTP credentials in the <a href="">settings menu</a> in order to send email using SMTP.', 'primer' ), esc_url( $settings_url ) );
					?>
				</p>
			</div>
		<?php }

        $wc_data_sync = get_option('woocommerce_custom_orders_table_data_sync_enabled');
        if ($wc_data_sync == 'no') {
            ?>
            <div class="error">
                <p>
                    <?php
                    printf( __( 'Please enable compatibility mode in WooCommerce <a href="admin.php?page=wc-settings&tab=advanced&section=features">settings</a> in order to use Primer.', 'primer' ) );
                    ?>
                </p>
            </div> <?php
        }
	}

	public function get_password() {
		$request_password = isset($_POST['primer_smtp_password']) ? $_POST['primer_smtp_password'] : '';
		$temp_password = isset( $this->opts['smtp_settings']['password'] ) ? $this->opts['smtp_settings']['password'] : $request_password;
		if ( '' === $temp_password ) {
			return '';
		}

		try {

			if ( get_option('primer_pass_encrypted') ) {
				// this is encrypted password
				$cryptor = Primer_SMTP_Utils::get_instance();
				$decrypted = $cryptor->decrypt_password( $temp_password );
				//check if encryption option is disabled
				if ( empty( $this->opts['smtp_settings']['encrypt_pass'] ) ) {
					//it is. let's save decrypted password
					$this->opts['smtp_settings']['password'] = $this->encrypt_password( addslashes( $decrypted ) );
					update_option('primer_emails', $this->opts);
				}
				return $decrypted;
			}
		} catch ( Exception $e ) {
			return '';
		}

		$password     = '';
		$decoded_pass = base64_decode( $temp_password ); //phpcs:ignore
		/* no additional checks for servers that aren't configured with mbstring enabled */
		if ( ! function_exists( 'mb_detect_encoding' ) ) {
			return $decoded_pass;
		}
		/* end of mbstring check */
		if ( base64_encode( $decoded_pass ) === $temp_password ) { //phpcs:ignore
			//it might be encoded
			if ( false === mb_detect_encoding( $decoded_pass ) ) {  //could not find character encoding.
				$password = $temp_password;
			} else {
				$password = base64_decode( $temp_password ); //phpcs:ignore
			}
		} else { //not encoded
			$password = $temp_password;
		}
		return stripslashes( $password );
	}

	public function encrypt_password( $pass ) {
		if ( '' === $pass ) {
			return '';
		}

			$password = base64_encode( $pass ); //phpcs:ignore
			update_option( 'primer_pass_encrypted', false );

		return $password;
	}

	public function credentials_configured() {
		$credentials_configured = true;
		if ( ! isset( $this->opts['from_email_field'] ) || empty( $this->opts['from_email_field'] ) ) {
			$credentials_configured = false;
		}
		return $credentials_configured;
	}
}

PrimerSMTP::get_instance();

add_action('wp_ajax_test_send_form', 'test_send_form');
function test_send_form() {
	var_dump($_POST);
	wp_die();
}
