<?php
/**
 * Our abstract Metabox class which is extended by the Display class.
 *
 * @package ObjectMetaExaminer
 */
namespace ObjectMetaExaminer;

abstract class Metabox {

	/**
	 * Array of object types for which this metabox will register.
	 *
	 * @var array
	 */
	protected $object_types = [];

	/**
	 * Metabox ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Metabox title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Context of the metabox.
	 *
	 * @var string
	 */
	public $context = 'normal';

	/**
	 * Priority of the metabox.
	 *
	 * @var string
	 */
	public $priority = 'default';

	/**
	 * Initialize the metabox.
	 *
	 * @param string $id           Metabox ID.
	 * @param string $title        Metabox title.
	 * @param array  $object_types Array of post types where we show it.
	 * @param string $context      Metabox context.
	 * @param string $priority     Metabox priority.
	 */
	public function init( string $id, string $title, array $object_types, string $context = 'normal', string $priority = 'default' ) {
		$this->object_types = $object_types;
		$this->id           = $id;
		$this->title        = $title;
		$this->context      = $context;
		$this->priority     = $priority;

		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ], 10, 2 );
	}

	/**
	 * Add meta box.
	 *
	 * @param string $object_type Object Type.
	 */
	public function add_meta_box( string $object_type ) {
		add_meta_box( $this->id, $this->title, [ $this, 'render' ], $object_type, $this->context, $this->priority );
	}

	/**
	 * Register a metabox.
	 *
	 * @param string                              $object_type The object type.
	 * @param \WP_Post|\WC_Order|\WC_Subscription $object      The post object, or order/subscription if HPOS screen.
	 */
	public function register_metabox( $object_type, $object ) {
		if ( ! in_array( $object_type, $this->object_types, true ) ) {
			return;
		}

		if ( ! $this->show_metabox( $object_type, $object ) ) {
			return;
		}

		$this->add_meta_box( $object_type );
	}


	/**
	 * Show metabox.
	 *
	 * @param string                              $object_type The object type.
	 * @param \WP_Post|\WC_Order|\WC_Subscription $object      The post object, or order/subscription if HPOS screen.
	 *
	 * @return bool
	 */
	public function show_metabox( $object_type, $object ) : bool {
		return true;
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post|\WC_Order|\WC_Subscription $object The post object, or order/subscription if HPOS screen.
	 */
	public function render( $object ) {
	}

	/**
	 * Find the object type, whether it's a post, order or subscription. HPOS compatible.
	 *
	 * @param \WP_Post|\WC_Order|\WC_Subscription $object The post object, or order/subscription if HPOS screen.
	 *
	 * @return string
	 */
	public function get_type( $object ) {
		$type = $object instanceof \WP_Post ? $object->post_type : $object->get_type();

		return $type;
	}

}
