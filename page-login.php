<?php
/**
 *	Template Name: Login
 */

// Process POST and enqueue scripts
$custom_login_register->login_user();
$custom_login_register->scripts();

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" role="main">

			<?php
				while ( have_posts() ) {
					the_post();

					// Display the login form
					echo '<div style="width: 320px; display: block; margin: 0 auto;">';
					$custom_login_register->login_form();
					echo '</div>';
				}
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
