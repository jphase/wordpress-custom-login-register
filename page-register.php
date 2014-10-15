<?php
/**
 *	Template Name: Register
 */

// Process POST and enqueue scripts
$custom_login_register->register_user();
$custom_login_register->scripts();

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="width-100" role="main">

			<?php
				while ( have_posts() ) {
					the_post();

					// Display the registration form
					$custom_login_register->registration_form( get_the_title() );
				}
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
