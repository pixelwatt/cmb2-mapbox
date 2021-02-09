<?php
/*
Plugin Name: CMB2 Mapbox
Plugin URI:
Description: This plugin adds a new CMB2 fieldtype for adding a single point to a Mapbox map. This plugin requires CMB2 and a Mapbox access token.
Version: 1.0.1
Author: Rob Clark
Author URI: https://robclark.io
License: GPLv2 or later
Text Domain: cmb2-mapbox
GitHub Plugin URI: pixelwatt/cmb2-mapbox
*/

add_action( 'cmb2_admin_init', 'cmb2_mapbox_options_metabox' );

function cmb2_mapbox_options_metabox() {

	/**
	 * Registers options page menu item and form.
	 */
	$cmb_options = new_cmb2_box(
		array(
			'id'           => 'cmb2_mapbox_plugin_options_metabox',
			'title'        => esc_html__( 'CMB2 Mapbox Configuration', 'cmb2-mapbox' ),
			'object_types' => array( 'options-page' ),

			/*
			 * The following parameters are specific to the options-page box
			 * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
			 */

			'option_key'      => 'cmb2_mapbox', // The option key and admin menu page slug.
			'menu_title'      => esc_html__( 'CMB2 Mapbox', 'cmb2-mapbox' ), // Falls back to 'title' (above).
			'position'        => 2, // Menu position. Only applicable if 'parent_slug' is left empty.
			'parent_slug'     => 'options-general.php',
			'save_button'     => esc_html__( 'Save Mapbox Settings', 'cmb2-mapbox' ), // The text for the options-page save button. Defaults to 'Save'.
		)
	);

	$cmb_options->add_field(
		array(
			'name'     => __( 'Access Token', 'cmb2-mapbox' ),
			'id'       => 'api_token',
			'type'     => 'textarea',
		)
	);

	$cmb_options->add_field(
		array(
			'name'     => __( 'Map Center', 'cmb2-mapbox' ),
			'id'       => 'map_center',
			'type'     => 'text',
			'attributes' => array(
				'placeholder' => 'lng, lat',
			),
		)
	);
}


function cmb2_mapbox_scripts() {
	wp_enqueue_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.1.0/mapbox-gl.css', array(), null );
	wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.1.0/mapbox-gl.js', array(), null );
	if ( is_admin() ) {
		wp_enqueue_style( 'mapbox-gl-draw', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.2.0/mapbox-gl-draw.css', array(), null );
		wp_enqueue_script( 'mapbox-gl-draw', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.2.0/mapbox-gl-draw.js', array( 'mapbox-gl' ), null );
	}
}

add_action( 'wp_enqueue_scripts', 'cmb2_mapbox_scripts' );
add_action( 'admin_enqueue_scripts', 'cmb2_mapbox_scripts' );

function cmb2_render_mapbox_map_callback( $field, $value, $object_id, $object_type, $field_type ) {
	$options = get_option( 'cmb2_mapbox' );
	if ( is_array( $options ) ) {
		if ( isset( $options['api_token'] ) ) {
			$value = wp_parse_args(
				$value,
				array(
					'lat' => '',
					'lng'  => '',
					'lnglat' => '',
				),
			);
			?>
				<div id='map' style='width: 100%; height: 400px;'></div>
				<script>
					mapboxgl.accessToken = '<?php echo $options['api_token']; ?>';
					var map = new mapboxgl.Map({
						container: 'map',
						style: 'mapbox://styles/mapbox/streets-v11',
						<?php if ( ! empty( $value['lnglat'] ) ) { ?>
							zoom: 13,
							center: [<?php echo $value['lnglat']; ?>]
						<?php } else { ?>
							zoom: 11<?php echo ( isset( $options['map_center'] ) ? ',center: [' . $options['map_center'] . ']' : '' ); ?>
						<?php } ?>
					});
					map.addControl(new mapboxgl.NavigationControl());
					var draw = new MapboxDraw({
						displayControlsDefault: false,
						controls: {
							point: true,
							trash: true
						},
						styles: [
					    {
					      'id': 'highlight-active-points',
					      'type': 'circle',
					      'filter': ['all',
					        ['==', '$type', 'Point'],
					        ['==', 'meta', 'feature'],
					        ['==', 'active', 'true']],
					      'paint': {
					        'circle-radius': 8,
					        'circle-color': '#F29F05'
					      }
					    },
					    {
					      'id': 'points-are-blue',
					      'type': 'circle',
					      'filter': ['all',
					        ['==', '$type', 'Point'],
					        ['==', 'meta', 'feature'],
					        ['==', 'active', 'false']],
					      'paint': {
					        'circle-radius': 6,
					        'circle-color': '#3868A6'
					      }
					    }
					  ]
					});
					map.addControl(draw);

					map.on('draw.create', updateEditor);
					map.on('draw.delete', updateEditor);
					map.on('draw.update', updateEditor);

					function updateEditor(e) {
						if (e.type !== 'draw.delete') {
							var data = draw.getAll();
							if (data.features.length > 1) {
								draw.delete( data.features[0].id );
								data.features.shift();
							}
							var coords = String( data.features[0].geometry.coordinates ).split(",");
							document.getElementById("<?php echo $field_type->_id( '_lnglat' ); ?>").value = data.features[0].geometry.coordinates;
							document.getElementById("<?php echo $field_type->_id( '_lng' ); ?>").value = coords[0];
							document.getElementById("<?php echo $field_type->_id( '_lat' ); ?>").value = coords[1];
						} else {
							draw
								.deleteAll()
								.getAll();
							document.getElementById("<?php echo $field_type->_id( '_lnglat' ); ?>").value = '';
							document.getElementById("<?php echo $field_type->_id( '_lng' ); ?>").value = '';
							document.getElementById("<?php echo $field_type->_id( '_lat' ); ?>").value = '';
						}
					}
					<?php if ( ! empty( $value['lnglat'] ) ) { ?>
						map.on('load', function() {
							draw.add({ type: 'Point', coordinates: [<?php echo $value['lnglat']; ?>] });
						});
					<?php } ?>

				</script>
					<style>
						input.cmb2-mapbox-entry-field {
							width: 30%;
							margin-right: 3%;
							margin-left: 0 !important;
							margin-top: 16px;
							appearance: none;
							border-radius: 0;
							border: none;
							border-bottom: 2px solid #E3E3E3;
						}
					</style>
					<div class="entry-fields"><p style="display: none; visibility: hidden;"><label for="<?php echo $field_type->_id( '_lng' ); ?>">Marker Longitude</label></p><?php
						echo $field_type->input(
							array(
								'name'  => $field_type->_name( '[lng]' ),
								'id'    => $field_type->_id( '_lng' ),
								'class' => 'cmb2-mapbox-entry-field',
								'value' => $value['lng'],
								'desc'  => '',
								'placeholder' => 'Longitude',
							)
						);
					?><p style="display: none; visibility: hidden;"><label for="<?php echo $field_type->_id( '_lat' ); ?>">Marker Latitude</label></p><?php
						echo $field_type->input(
							array(
								'name'  => $field_type->_name( '[lat]' ),
								'id'    => $field_type->_id( '_lat' ),
								'class' => 'cmb2-mapbox-entry-field',
								'value' => $value['lat'],
								'desc'  => '',
								'placeholder' => 'Latitude',
							)
						);
					?><p style="display: none; visibility: hidden;"><label for="<?php echo $field_type->_id( '_lnglat' ); ?>">Longitude,Latitude</label></p><?php
						echo $field_type->input(
							array(
								'name'  => $field_type->_name( '[lnglat]' ),
								'id'    => $field_type->_id( '_lnglat' ),
								'class' => 'cmb2-mapbox-entry-field',
								'value' => $value['lnglat'],
								'desc'  => '',
								'placeholder' => 'Longitude,Latitude',
							)
						);
					?></div>
			<?php
		}
	}

}

add_filter( 'cmb2_render_mapbox_map', 'cmb2_render_mapbox_map_callback', 10, 5 );
