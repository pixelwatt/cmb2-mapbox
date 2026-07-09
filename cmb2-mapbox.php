<?php
/**
 * Plugin Name: CMB2 Mapbox
 * Plugin URI:
 * Description: This plugin adds a new CMB2 fieldtype for adding a single point to a Mapbox map. This plugin requires CMB2 and a Mapbox access token.
 * Version: 2.0.0-beta2
 * Author: Rob Clark
 * Author URI: https://robclark.io
 * License: GPLv2 or later
 * Text Domain: cmb2-mapbox
 * GitHub Plugin URI: https://github.com/pixelwatt/cmb2-mapbox
 */

// apply_filters( 'cmb2_mapbox_use_method', false );

function cmb2_mapbox_method() {
	return apply_filters( 'cmb2_mapbox_use_method', false );
}

add_action( 'cmb2_admin_init', 'cmb2_mapbox_options_metabox' );

function cmb2_mapbox_options_metabox() {
	if ( ! cmb2_mapbox_method() ) {
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
				'id'       => 'mapbox_api_token',
				'type'     => 'textarea',
			)
		);

		$cmb_options->add_field(
			array(
				'name'     => __( 'Map Center', 'cmb2-mapbox' ),
				'id'       => 'mapbox_map_center',
				'type'     => 'text',
				'attributes' => array(
					'placeholder' => 'lng, lat',
				),
			)
		);

	}
}

add_action( 'method_options_before_fields', 'cmb2_mapbox_register_custom_options', 99 );

function cmb2_mapbox_register_custom_options( $cmb_options ) {
	if ( cmb2_mapbox_method() ) {
		$cmb_options->add_field(
			array(
				'name'     => __( 'CMB2 Mapbox Configuration', 'rosslyn' ),
				'id'       => 'mapbox_options',
				'type'     => 'title',
			)
		);

		$cmb_options->add_field(
			array(
				'name'     => __( 'Access Token', 'cmb2-mapbox' ),
				'id'       => 'mapbox_api_token',
				'type'     => 'textarea',
			)
		);

		$cmb_options->add_field(
			array(
				'name'     => __( 'Map Center', 'cmb2-mapbox' ),
				'id'       => 'mapbox_map_center',
				'type'     => 'text',
				'attributes' => array(
					'placeholder' => 'lng, lat',
				),
			)
		);
	}
}


function cmb2_mapbox_scripts() {
	wp_enqueue_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.23.1/mapbox-gl.css', array(), null );
	wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.23.1/mapbox-gl.js', array(), null );
	if ( is_admin() ) {
		wp_enqueue_style( 'mapbox-gl-draw', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.5.1/mapbox-gl-draw.css', array(), null );
		wp_enqueue_script( 'mapbox-gl-draw', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.5.1/mapbox-gl-draw.js', array( 'mapbox-gl' ), null );
	}
}

add_action( 'wp_enqueue_scripts', 'cmb2_mapbox_scripts' );
add_action( 'admin_enqueue_scripts', 'cmb2_mapbox_scripts' );

function cmb2_render_mapbox_map_callback( $field, $value, $object_id, $object_type, $field_type ) {
	$options = get_option( ( cmb2_mapbox_method() ? 'method_options' : 'cmb2_mapbox' ) );
	if ( is_array( $options ) ) {
		if ( isset( $options['mapbox_api_token'] ) ) {
			$value = wp_parse_args(
				$value,
				array(
					'lat'             => '',
					'lng'             => '',
					'lnglat'          => '',
					'geocode_on_save' => '',
				),
			);
			?>
				<div id='map' style='width: 100%; height: 400px;'></div>
				<script>
					mapboxgl.accessToken = '<?php echo $options['mapbox_api_token']; ?>';
					var map = new mapboxgl.Map({
						container: 'map',
						style: 'mapbox://styles/mapbox/streets-v11',
						<?php if ( ! empty( $value['lnglat'] ) ) { ?>
							zoom: 13,
							center: [<?php echo $value['lnglat']; ?>]
						<?php } else { ?>
							zoom: <?php echo ( cmb2_mapbox_check_array_key( $field->args, 'default_zoom' ) ? $field->args['default_zoom'] : '11' ); ?>,
							center: [<?php echo ( cmb2_mapbox_check_array_key( $options, 'mapbox_map_center' ) ? $options['mapbox_map_center'] : '-95.7129,37.0902' ); ?>]
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
						if ( ( cmb2_mapbox_check_array_key( $field->args, 'geocode_serialized_key' ) ) || ( cmb2_mapbox_check_array_key( $field->args, 'geocode_key_array' ) ) ) {
					?>
						<p><label for="<?php echo $field_type->_id( '_geocode_on_save' ); ?>"><?php
						echo $field_type->input(
							array(
								'type'    => 'checkbox',
								'name'    => $field_type->_name( '[geocode_on_save]' ),
								'id'      => $field_type->_id( '_geocode_on_save' ),
								'value'   => 'checked',
								'checked' => false,
								'desc'    => '',
							)
						);
					?> Geocode on Save</label></p>
					<?php
						}
					?>
			<?php
		}
	}

}

add_filter( 'cmb2_render_mapbox_map', 'cmb2_render_mapbox_map_callback', 10, 5 );

function cmb2_sanitize_mapbox_map_callback( $override_value, $value, $object_id, $field_args, $field ) {
	if ( cmb2_mapbox_check_array_key( $value, 'lnglat' ) ) {
		$value['lnglat'] = str_replace( ' ', '', $value['lnglat'] );
	}
	if ( ( cmb2_mapbox_check_array_key( $value, 'lnglat' ) ) && ( ! cmb2_mapbox_check_array_key( $value, 'lng' ) ) && ( ! cmb2_mapbox_check_array_key( $value,'lat' ) ) ) {
		$coords = explode( ',', $value['lnglat'] );
		if ( is_array( $coords ) ) {
			if ( 2 == count( $coords ) ) {
				$value['lng'] = $coords[0];
				$value['lat'] = $coords[1];
			}
		}
	} elseif ( ( ! cmb2_mapbox_check_array_key( $value, 'lnglat' ) ) && ( cmb2_mapbox_check_array_key( $value, 'lng' ) ) && ( cmb2_mapbox_check_array_key( $value,'lat' ) ) ) {
		$value['lnglat'] = $value['lng'] . ',' . $value['lat'];
	}

	if ( cmb2_mapbox_check_array_key( $value, 'geocode_on_save' ) ) {
		$value['geocode_on_save'] = '';
		$geocoded = array();
		if ( cmb2_mapbox_check_array_key( $field_args, 'geocode_serialized_key' ) ) {
			$geocoded = cmb2_mapbox_geocode( $object_id, $field_args['geocode_serialized_key'], true );
		} elseif ( cmb2_mapbox_check_array_key( $field_args, 'geocode_key_array' ) ) {
			$geocoded = cmb2_mapbox_geocode( $object_id, $field_args['geocode_key_array'], false );
		}
		if ( ( cmb2_mapbox_check_array_key( $geocoded, 'lng' ) ) && ( cmb2_mapbox_check_array_key( $geocoded, 'lat' ) ) && ( cmb2_mapbox_check_array_key( $geocoded, 'lnglat' ) ) ) {
			$value['lnglat'] = $geocoded['lnglat'];
			$value['lng'] = $geocoded['lng'];
			$value['lat'] = $geocoded['lat'];
		}
	}

	return $value;
}
add_filter( 'cmb2_sanitize_mapbox_map', 'cmb2_sanitize_mapbox_map_callback', 48, 5 );

function cmb2_mapbox_geocode( $pid, $kdata, $serialized ) {
	$addr = '';
	$geo = array(
		'lng' => '',
		'lat' => '',
		'lnglat' => '',
	);
	$options = get_option( ( cmb2_mapbox_method() ? 'method_options' : 'cmb2_mapbox' ) );
	if ( is_array( $options ) ) {
		if ( isset( $options['mapbox_api_token'] ) ) {
			if ( ( $serialized ) && ( is_string( $kdata ) ) ) {
				$addr_array = get_post_meta( $pid, $kdata, true );
				if ( is_array( $addr_array ) ) {
					if ( 0 < count( $addr_array ) ) {
						$addr = implode( ', ', $addr_array );
					}
				}
			} elseif ( is_array( $kdata ) ) {
				$addr_array = array();
				foreach ( $kdata as $key => $value ) {
					$data = get_post_meta( $pid, $value, true );
					if ( ! empty( $data ) ) {
						$addr_array[] = $data;
					}
					if ( 0 < count( $addr_array ) ) {
						$addr = implode( ', ', $addr_array );
					}
				}
			}
			if ( ! empty( $addr ) ) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, 'https://api.mapbox.com/search/geocode/v6/forward?q=' . urlencode( $addr ) . '&limit=1&types=place,address&access_token=' . $options['mapbox_api_token'] );
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$output = curl_exec($curl);
				curl_close($curl);
				$output = json_decode( $output, true );
				if ( cmb2_mapbox_check_array_key( $output, 'features' ) ) {
					if ( cmb2_mapbox_check_array_key( $output['features'], 0 ) ) {
						if ( cmb2_mapbox_check_array_key( $output['features'][0], 'geometry' ) ) {
							if ( cmb2_mapbox_check_array_key( $output['features'][0]['geometry'], 'coordinates' ) ) {
								if ( ( cmb2_mapbox_check_array_key( $output['features'][0]['geometry']['coordinates'], 0 ) ) && ( cmb2_mapbox_check_array_key( $output['features'][0]['geometry']['coordinates'], 1 ) ) ) {
									$lng =  $output['features'][0]['geometry']['coordinates'][0];
									$lat =  $output['features'][0]['geometry']['coordinates'][1];
									$geo['lng'] = $lng;
									$geo['lat'] = $lat;
									$geo['lnglat'] = $lng . ',' . $lat;
								}
							}
						}
					}
				}
			}
		}
	}
	return $geo;
}

function cmb2_mapbox_check_array_key( $item, $key ) {
	$output = false;
	if ( is_array( $item ) ) {
		if ( array_key_exists( $key, $item ) ) {
			if ( ! empty( $item["{$key}"] ) ) {
				$output = true;
			}
		}
	}
	return $output;
}

if ( ! class_exists( 'CMB2_MB_Map' ) ) {
	class CMB2_MB_Map {
		protected $plugin_options = array();
		protected $map_options = array();
		protected $geo = array();

		function __construct() {
			$this->set_options();

			// Set up arrays for geo
			$this->geo['markers'] = array();
		}

		private function set_options() {
			// Set plugin options
			$defaults = array(
				'mapbox_api_token' => '',
				'mapbox_map_center' => '-95.7129,37.0902',
			);
			$this->plugin_options = wp_parse_args( get_option( ( cmb2_mapbox_method() ? 'method_options' : 'cmb2_mapbox' ) ), $defaults );
		}

		public function set_map_options( $args ) {
			$defaults = array(
				'id'       => 'map',
				'class'    => 'cmb2-mapbox-map',
				'width'    => '100%',
				'height'   => '400px',
				'pitch'    => '0',
				'zoom'     => '16',
				'center'   => '-95.7129,37.0902',
				'mapstyle' => 'mapbox://styles/mapbox/streets-v11',
				'terrain'  => false,
				'include_nav'  => true,
			);
			$this->map_options = wp_parse_args( $args, $defaults );
		}

		public function add_marker( $geo, $tooltip, $color, $scale = 1, $elem = '' ) {
			$this->geo['markers'][] = array(
				'geo'     => $geo,
				'tooltip' => $tooltip,
				'color' => $color,
				'scale' => $scale,
				'element' => $elem,
			);
			return;
		}

		public function build_map() {
			$output = '';
			$marker_html = '';
			$markers = array();
			if ( 0 < count( $this->geo['markers'] ) ) {
				$i = 1;
				foreach( $this->geo['markers'] as $marker ) {
					$html = '
						' . ( ! empty( $marker['element'] ) ? 'const el' . $i . ' = document.createElement(\'div\');' : '' ) . '
						' . ( ! empty( $marker['element'] ) ? 'el' . $i . '.innerHTML = \'' . $marker['element'] . '\';' : '' ) . '
						const popup' . $i . ' = new mapboxgl.Popup({ offset: 25 }).setMaxWidth("300px").setHTML(
												\'' . '<div class="mapbox-popup-content-wrap">' . $marker['tooltip'] . '</div>\'
												);

						const marker' . $i . ' = new mapboxgl.Marker({ color: \'' . $marker['color'] . '\', scale: ' . $marker['scale'] . ( ! empty( $marker['element'] ) ? ', element: el' . $i : '' ) . ' })
							.setLngLat([' . $marker['geo']['lnglat'] . '])
							.setPopup(popup' . $i . ')
							.addTo(map);
					';
					$markers[] = array(
						'html' => $html,
						'lat' => $marker['geo']['lat']
					);
					$i++;
				}
				array_multisort( array_column( $markers, 'lat' ), SORT_DESC, $markers );
				foreach ($markers as $marker) {
					$marker_html .= $marker['html'];
				}
				$output .= '
					<div id="' . $this->map_options['id'] . '" class="' . $this->map_options['class'] . '" style="height: ' . $this->map_options['height'] . '; width: ' . $this->map_options['width'] . ';"></div>
					<script>
						mapboxgl.accessToken = \'' . $this->plugin_options['mapbox_api_token'] . '\';
					  	var map = new mapboxgl.Map({
						    container: \'' . $this->map_options['id'] . '\',
						    style: \'' . $this->map_options['mapstyle'] . '\',
						    zoom: ' . $this->map_options['zoom'] . ',
						    pitch: ' . $this->map_options['pitch'] . ',
						    scrollZoom: false,
						    center: [' . $this->map_options['center'] . '],
					  	});
					  	map.on(\'load\', function () {
							var nav = new mapboxgl.NavigationControl();
							' . ( $this->map_options['include_nav'] ? 'map.addControl(nav, \'top-left\');' : '' ) . '
							' . $marker_html . '
				';
				if ( $this->map_options['terrain'] ) {
					$output .= '
								map.addSource(\'dem\', {
									\'type\': \'raster-dem\',
									\'url\': \'mapbox://mapbox.terrain-rgb\'
								});
								map.addLayer({
									\'id\': \'hillshading\',
									\'source\': \'dem\',
									\'type\': \'hillshade\'
								},);
							
					';
				}
				$output .= '
					});
					</script>
				';
			}
			return $output;
		}
	}
}
