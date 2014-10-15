<?php
namespace yourapp;

class Registration {

	// Page slugs
	private $login_slug 			= '/login';
	private $register_slug 			= '/register';
	private $profile_slug 			= '/my-account';
	private $terms_slug				= '/terms';
	private $privacy_slug			= '/privacy';

	// reCAPTCHA variables
	private $captcha_public_key 	= 'your_recaptcha_public_key';
	private $captcha_private_key 	= 'your_recaptcha_private_key';
	private $captcha_error 			= false;

	function __construct() {
		// Actions
		add_action( 'after_theme_setup', array( $this, 'login_user' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'login_form_register', array( $this, 'register' ) );
		add_action( 'login_form_login', array( $this, 'login' ) );
		add_action( 'wp_ajax_username_availability', array( $this, 'username_availability' ) );
		add_action( 'wp_ajax_nopriv_username_availability', array( $this, 'username_availability' ) );
		add_action( 'user_register', array( $this, 'user_notification' ), 10 );
		
		// Filters
		add_filter( 'login_errors', array( $this, 'login_errors' ), 10, 3 );
	}

	/**
	 * Login / Register form scripts and styles
	 */
	function scripts() {

		// Scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'password-strength-meter' );
		wp_enqueue_script( 'login-register', get_stylesheet_directory_uri() . '/inc/js/login-register.js', array( 'jquery' ), false, false );

		// Styles
		wp_enqueue_style( 'dashicons' );

	}

	/**
	 * Take over native WordPress login action and forward it off to our custom page template
	 */
	function login() {
		header( 'Location: ' . $this->login_slug );
	}

	/**
	 * Take over native WordPress registration action and forward it off to our custom page template
	 */
	function register() {
		header( 'Location: ' . $this->register_slug );
	}

	/**
	 * Render registration form
	 *
	 * @param	string	$title 	The heading that displays above the registration form
	 */
	function registration_form( $title ) {

		// Initialize variables
		$first_name = isset( $_POST['first_name'] ) ? $_POST['first_name'] : '';
		$last_name = isset( $_POST['last_name'] ) ? $_POST['last_name'] : '';
		$email = isset( $_POST['email'] ) ? $_POST['email'] : '';
		$confirm_email = isset( $_POST['confirm_email'] ) ? $_POST['confirm_email'] : '';
		$username = isset( $_POST['username'] ) ? $_POST['username'] : '';
		global $socialize;

		// Process POST
		$registration = $this->register_user();
		?>

		<div style="width: 320px; margin: 0 auto;">
			<h1 style="text-align: center;"><?php echo $title; ?></h1>
			<form name="registerform" id="registerform" method="post" autocomplete="off">
				<?php
					if ( ! empty( $registration->errors ) ) {
						echo '<table><tbody>';
						foreach ( $registration->errors as $field => $error ) {
							echo '<div class="form-error">' . $error[0] . '</div>';
						}
						echo '</tbody></table>';
					}
				?>
				<p>
					<label for="first_name">First Name:<br>
						<input type="text" name="first_name" id="first_name" class="input width-100" value="<?php echo esc_attr( $first_name ); ?>" size="25">
					</label>
				</p>
				<p>
					<label for="last_name">Last Name:<br>
						<input type="text" name="last_name" id="last_name" class="input width-100" value="<?php echo esc_attr( $last_name ); ?>" size="25">
					</label>
				</p>
				<p>
					<label for="email">E-mail:<br>
						<input type="text" name="email" id="email" class="input width-100" value="<?php echo esc_attr( $email ); ?>" size="25">
					</label>
				</p>
				<p>
					<label for="confirm_email">Confirm E-mail:<br>
						<input type="text" name="confirm_email" id="confirm_email" class="input width-100" value="<?php echo esc_attr( $confirm_email ); ?>" size="25">
					</label>
				</p>
				<p>
					<label for="username">Pick a Username:<br>
						<input type="text" name="username" id="username" class="input width-100" value="<?php echo esc_attr( $username ); ?>" size="25" autocomplete="off">
						<span id="username-check"></span>
						<?php wp_nonce_field( 'username_validate', 'username_validate' ); ?>
					</label>
				</p>
				<p>
					<label for="password">Password:<br>
						<input type="password" name="password" id="password" class="input width-100" value="" size="25">
						<span id="password-strength"></span>
					</label>
				</p>
				<?php $this->recaptcha(); ?>
				<p>
					<label for="terms_of_service" style="font-size: 10px;">
						<input type="checkbox" name="terms_of_service" id="terms_of_service">
						I agree to <?php bloginfo('name'); ?>'s <a href="<?php echo $this->terms_slug; ?>" target="_blank">Terms of Service</a> and <a href="<?php echo $this->privacy_slug; ?>" target="_blank">Privacy Policy</a>
					</label>
				</p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large width-100" value="Register">
				</p>
			</form>
			<p>Already have an account? &nbsp;&nbsp;<a href="<?php echo $this->login_slug; ?>">Log In!</a></p>
		</div>

		<?php
	}

	/**
	 * Display login form
	 */
	function login_form( $title = '' ) {
		if ( get_current_user_id() && empty( $title ) ) return;
		?>

		<h1 style="text-align: center;"><?php echo $title; ?></h1>
		<form name="loginform" id="loginform" action="/login" method="post">
			<p>
				<label for="username">Username:<br>
					<input type="text" name="log" id="username" class="input width-100" size="25">
				</label>
			</p>
			<p>
				<label for="password">Password:<br>
					<input type="password" name="pwd" id="password" class="input width-100" size="25">
				</label>
			</p>
			<p>
				<label for="remember">
					<input type="checkbox" name="rememberme" id="remember"> Remember Me
				</label>
			</p>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large width-100" value="Log In">
			</p>
			<p>
				<a href="<?php echo wp_lostpassword_url(); ?>">Forgot your password?</a>
			</p>
			<?php wp_nonce_field( 'your_custom_login', 'your_custom_login' ); ?>
		</form>

		<?php
	}

	/**
	 * Function to process $_POST on custom login form
	 */
	function login_user() {
		if ( is_user_logged_in() ) {
			// Forward them off to their profile
			header( 'Location: ' . $this->profile_slug );
		}
		if ( $_POST && isset( $_POST['your_custom_login'] ) && wp_verify_nonce( $_POST['your_custom_login'], 'your_custom_login' ) ) {
			if ( ! empty( $_POST['log'] ) && ! empty( $_POST['pwd'] ) ) {
				// Sign in the user
				$user = wp_signon();
				if ( ! is_wp_error( $user ) ) {
					// Forward them off to their profile
					header( 'Location: ' . $this->profile_slug );
				}
				return $user;
			}
		}
	}

	/**
	 * Function to process $_POST on custom register form
	 */
	function register_user() {
		if ( $_POST ) {
			$registration = $this->valid_registration();
			if ( is_wp_error( $registration ) || ! $registration ) {
				// Return validation errors
				return $registration;
			} else {
				// Create the user and forward them off to where they came from
				$new_user = $this->create_registration_user();
				if ( $new_user ) {
					// Forward the newly created user to their profile
					header( 'Location: ' . $this->profile_slug );
					return true;
				} else {
					// Return errors produced during new user creation
					return $new_user;
				}
			}
		}
		return false;		
	}
	
	/**
	 * Filter body class on login pages
	 */
	function login_body_class( $classes ) {
		// Add forgot-pass class if checkemail is set (see js/afkt-login-register.js)
		if ( isset( $_GET['checkemail'] ) ) {
			array_push( $classes, 'forgot-pass' );
		}
		return $classes;
	}

	/**
	 * Check username availability on registration form
	 */
	function username_availability() {
		if ( ! empty( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'username_validate' ) ) {
			$username = sanitize_user( $_POST['username'], true );
			if ( strlen( $username ) > 4 ) {
				$user = get_user_by( 'login', $username );
				if ( is_object( $user ) ) {
					wp_send_json_error( $username . ' is taken' );
				} else {
					wp_send_json_success( $username );
				}
			} else {
				wp_send_json_error( 'Invalid username' );
			}
		} else {
			wp_send_json_error( 'Invalid nonce' );
		}
		wp_send_json_error( 'An unknown error occurred' );
	}

	/**
	 * Validate POST on registration form
	 *
	 * @return mixed	False or WP_Error if POST is invalid, HTTP_REFERER string if valid
	 */
	function valid_registration() {

		if ( ! empty( $_POST ) ) {

			// Initalize WP_Error object
			$registration = new \WP_Error;

			// Username errors
			if ( empty( $_POST['username'] ) ) {
				if ( strlen( sanitize_user( $_POST['username'] ) ) < 5 ) {
					// Check if username is too short
					$registration->add( 'username', '<tr><td><strong>ERROR:</strong></td><td>Username is too short. ' . sanitize_user( $_POST['username'] ) . '</td></tr>' );
				} else {
					// Check if user exists
					$user = get_user_by( 'login', sanitize_text_field( $_POST['username'] ) );
					if ( is_a( $user, 'WP_User' ) ) {
						$registration->add( 'username', '<tr><td><strong>ERROR:</strong></td><td>Username already exists.</td></tr>' );
					}
				}
			}

			// First name errors
			if ( empty( $_POST['first_name'] ) ) {
				$registration->add( 'first_name', '<tr><td><strong>ERROR:</strong></td><td>Please tell us your first name.</td></tr>' );
			}

			// Last name errors
			if ( empty( $_POST['last_name'] ) ) {
				$registration->add( 'last_name', '<tr><td><strong>ERROR:</strong></td><td>Please tell us your last name.</td></tr>' );
			}

			// Email errors
			if ( ! empty( $_POST['email'] ) ) {
				if ( ! is_email( sanitize_email( $_POST['email'] ) ) ) {
					// Check if email is valid
					$registration->add( 'email', '<tr><td><strong>ERROR:</strong></td><td>Your E-mail address is invalid.</td></tr>' );
				} else {
					// Check if user exists with this email
					$user = get_user_by( 'email', sanitize_email( $_POST['email'] ) );
					if ( is_a( $user, 'WP_User' ) ) {
						$registration->add( 'email', '<tr><td><strong>ERROR:</strong></td><td>E-mail address already exists.</td></tr>' );
					}
				}
			}

			// Confirm e-mail errors
			if ( ! empty( $_POST['email'] ) && ! empty( $_POST['confirm_email'] ) && ( sanitize_email( $_POST['email'] ) != sanitize_email( $_POST['confirm_email'] ) ) ) {
				$registration->add( 'confirm_email', '<tr><td><strong>ERROR:</strong></td><td>E-mail addresses didn\'t match.</td></tr>' );
			}

			// Password errors
			if ( empty( $_POST['password'] ) ) {
				$registration->add( 'password', '<tr><td><strong>ERROR:</strong></td><td>Please choose a password.</td></tr>' );
			}

			// reCAPTCHA errors
			if ( ! empty( $_POST['recaptcha_challenge_field'] ) ) {
				$captcha_check = recaptcha_check_answer( $this->captcha_private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );
				if ( ! $captcha_check->is_valid ) {
					$registration->add( 'captcha_error', '<tr><td><strong>ERROR:</strong></td><td>Incorrect CAPTCHA text.</td></tr>' );
					$this->captcha_error = $captcha_check->error;
				}
			} else {
				$registration->add( 'captcha_error', '<tr><td><strong>ERROR:</strong></td><td>Incorrect CAPTCHA text.</td></tr>' );
				$this->captcha_error = $captcha_check->error;
			}

			// TOS and Privacy errors
			if ( ! isset( $_POST['terms_of_service'] ) || $_POST['terms_of_service'] != 'on' ) {
				$registration->add( 'terms_of_service', '<tr><td><strong>ERROR:</strong></td><td>You must agree to our <a href="' . $this->terms_slug . '" target="_blank">Terms of Service</a> and <a href="' . $this->privacy_slug . '" target="_blank">Privacy Policy</a> to register.</td></tr>' );
			}

			// Return true if no errors found
			if ( empty( $registration->errors ) ) {
				return true;
			} else {
				return $registration;
			}

		}

		return false;

	}
	
	/**
	 * Create user from registration form POST
	 *
	 * @param 	boolean		True to login user upon successful creation
	 * @return 	boolean		True on success, False on fail
	 */
	function create_registration_user( $login_user = true ) {
		// Sanitize our inputs
		$username = sanitize_user( $_POST['username'] );
		$password = $_POST['password'];
		$email = sanitize_email( $_POST['email'] );

		// Create the user
		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		} else {

			// Update first name
			if ( ! empty( $_POST['first_name'] ) ) {
				wp_update_user( array( 'ID' => $user_id, 'first_name' => sanitize_text_field( $_POST['first_name'] ) ) );
			}

			// Update last name
			if ( ! empty( $_POST['last_name'] ) ) {
				wp_update_user( array( 'ID' => $user_id, 'last_name' => sanitize_text_field( $_POST['last_name'] ) ) );
			}

			// Add action for after the user (and their data) hits the database
			do_action( 'afkt_after_user_registered', $user_id );

			// Login the user
			if ( $login_user ) {
				wp_set_auth_cookie( $user_id );
			}

			return true;
		}

		return false;
	}

	/**
	 * Notify user after registration
	 *
	 * @param 	int			User ID from wp_create_user
	 */
	function user_notification( $user_id ) {
		$password = $_POST['password'];
		
		// Send user welcome email (on AFKT this is overridden in plugin afkt-email-mods - BV 10.9.14
		wp_new_user_notification( $user_id, $password ); 
	}

	/**
	 * Display reCAPTCHA
	 */
	function recaptcha() {

		?>
			<div id="recaptcha_widget" style="display:none">
				<div id="recaptcha_image"></div>
				<div class="recaptcha_only_if_incorrect_sol" style="color:red">Incorrect, please try again</div>
				<span class="recaptcha_only_if_image">
					Enter the words above:
					<div class="right">
						<div class="right">
							<a href="javascript:Recaptcha.showhelp()" title="Help with CAPTCHA">
								<div class="dashicons dashicons-editor-help"></div>
							</a>
						</div>
						<div class="recaptcha_only_if_image right">
							<a href="javascript:Recaptcha.switch_type(&#39;audio&#39;);" title="Play CAPTCHA as audio">
								<div class="dashicons dashicons-play-circle"></div>
							</a>
						</div>
						<div class="right">
							<a href="javascript:Recaptcha.reload()" title="Reload CAPTCHA image">
								<div class="dashicons dashicons-update"></div>
							</a>
						</div>
					</div>
				</span>
				<span class="recaptcha_only_if_audio">
					Type what you hear:
					<div class="right">
						<div class="recaptcha_only_if_audio">
							<a href="javascript:Recaptcha.switch_type(&#39;image&#39;)" title="Return to CAPTCHA image">
								<div class="dashicons dashicons-format-image"></div>
							</a>
						</div>
					</div>
				</span>
				<input type="text" id="recaptcha_response_field" class="width-100" name="recaptcha_response_field">
			</div>
		<?php
		echo recaptcha_get_html( $this->captcha_public_key, $this->captcha_error, true );

	}

}
