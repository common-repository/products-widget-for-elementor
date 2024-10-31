<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Register Custom WooCommerce Widget for Elementor
 */
add_action('elementor/widgets/widgets_registered', 'ibtg_register_woocommerce_widget');

function ibtg_register_woocommerce_widget() {
    if (!did_action('elementor/loaded')) {
        return;
    }

    if (class_exists('\Elementor\Widget_Base')) {

        class ibtg_WooCommerce_Elementor_Widget extends \Elementor\Widget_Base {

            public function get_name() {
                return 'ibt-woocommerce-products';
            }

            public function get_title() {
                return esc_html__('Product Collections', 'products-widget-for-elementor');
            }

            public function get_icon() {
                return 'eicon-woocommerce';
            }

            public function get_categories() {
                return ['general'];
            }

            protected function _register_controls() {
                // Enqueue and localize the JavaScript for this control
                wp_enqueue_script('ibt-woocommerce-elementor-ajax', plugin_dir_url(dirname(__FILE__)) . 'js/ajax.js', ['jquery'], '1.0', true);
                wp_localize_script('ibt-woocommerce-elementor-ajax', 'ibtWooElementor', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ibtg_woo_elementor')]);

                // [Add controls here - Example control added]
                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => esc_html__('Settings', 'products-widget-for-elementor'),
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

                $this->add_control(
                    'number_of_products',
                    [
                        'label' => esc_html__('Number of Products', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::NUMBER,
                        'min' => 1,
                        'max' => 100,
                        'default' => 4,
                    ]
                );

$this->add_responsive_control(
                    'columns',
                    [
                        'label' => __('Columns', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => '4',
                        'options' => [
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .products' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                        ],
                    ]
                );              

                $this->add_control(
                    'product_type',
                    [
                        'label' => __('Product Type', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => 'newest',
                        'options' => [
                            'best_sellers' => __('Best Sellers', 'products-widget-for-elementor'),
                            'newest' => __('Newest', 'products-widget-for-elementor'),
                            'on_sale' => __('On Sale', 'products-widget-for-elementor'),
                            'featured' => __('Featured', 'products-widget-for-elementor'),
                            'categories' => __('Categories', 'products-widget-for-elementor'),
                            'tags' => __('Tags', 'products-widget-for-elementor'),
                            'attributes' => __('Attributes', 'products-widget-for-elementor'),
                            'specific_products' => __('Specific Products', 'products-widget-for-elementor'),
                        ]
                    ]
                );


                $categories_array = [];
                $categories = get_terms('product_cat');
                foreach($categories as $category) {
                    $categories_array[$category->term_id] = esc_html($category->name);
                }

                $this->add_control(
                    'product_categories',
                    [
                        'label' => __('Select Categories', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $categories_array,
                        'condition' => [
                            'product_type' => 'categories',
                        ],
                    ]
                );
                
                // Getting available product tags:
                $tags_array = [];
                $tags = get_terms('product_tag');
                if (!is_wp_error($tags)) {
                    foreach($tags as $tag) {
                        $tags_array[$tag->slug] = esc_html($tag->name);
                    }
                }

                $this->add_control(
                    'product_tags',
                    [
                        'label' => __('Select Tags', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $tags_array,
                        'condition' => [
                            'product_type' => 'tags',
                        ],
                    ]
                );  
                
                $attribute_taxonomies = wc_get_attribute_taxonomies();
                $attributes_array = [];

                foreach($attribute_taxonomies as $attribute) {
                    $attributes_array[$attribute->attribute_name] = $attribute->attribute_label;
                }

                $this->add_control(
                    'product_attributes',
                    [
                        'label' => __('Select Attribute', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'options' => $attributes_array,
                        'condition' => [
                            'product_type' => 'attributes',
                        ],
                    ]
                );  

                $this->add_control(
                    'attribute_terms',
                    [
                        'label' => __('Select Attribute Terms', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => [], // We'll populate this dynamically with JavaScript later
                        'condition' => [
                            'product_type' => 'attributes',
                        ],
                    ]
                );



                // Fetch all products for selection
                $products_array = [];
                $products = wc_get_products(['numberposts' => -1]);
                foreach ($products as $product) {
                    $products_array[$product->get_id()] = $product->get_name();
                }

                $this->add_control(
                    'selected_products',
                    [
                        'label' => __('Select Products', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $products_array,
                        'condition' => [
                            'product_type' => 'specific_products',
                        ],
                    ]
                );


                // Control to show or hide the product title
                $this->add_control(
                    'show_title',
                    [
                        'label' => __('Show Title', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'products-widget-for-elementor'),
                        'label_off' => __('No', 'products-widget-for-elementor'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );

                // Control to show or hide the product price
                $this->add_control(
                    'show_price',
                    [
                        'label' => __('Show Price', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'products-widget-for-elementor'),
                        'label_off' => __('No', 'products-widget-for-elementor'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );

                // Control to show or hide the "Add to Cart" button
                $this->add_control(
                    'show_add_to_cart',
                    [
                        'label' => __('Show Add to Cart', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'products-widget-for-elementor'),
                        'label_off' => __('No', 'products-widget-for-elementor'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );
                
                $this->add_control(
                    'hide_sale_badge',
                    [
                        'label' => __('Hide Sale Badge', 'products-widget-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'products-widget-for-elementor'),
                        'label_off' => __('No', 'products-widget-for-elementor'),
                        'return_value' => 'yes',
                        'default' => 'no',
                    ]
                );
                

                $this->add_responsive_control(
                    'content_align',
                    [
                        'label' => __( 'Alignment', 'products-widget-for-elementor' ),
                        'type' => \Elementor\Controls_Manager::CHOOSE,
                        'options' => [
                            'left'    => [
                                'title' => __( 'Left', 'products-widget-for-elementor' ),
                                'icon' => 'eicon-text-align-left',
                            ],
                            'center' => [
                                'title' => __( 'Center', 'products-widget-for-elementor' ),
                                'icon' => 'eicon-text-align-center',
                            ],
                            'right' => [
                                'title' => __( 'Right', 'products-widget-for-elementor' ),
                                'icon' => 'eicon-text-align-right',
                            ],
                        ],
                        'default' => 'left',
                        'toggle' => true,
                        'selectors' => [
                            '{{WRAPPER}} .woocommerce ul.products li.product,
                             {{WRAPPER}} .woocommerce ul.products li.product .woocommerce-loop-product__title, 
                             {{WRAPPER}} .woocommerce ul.products li.product .price, 
                             {{WRAPPER}} .woocommerce ul.products li.product .add_to_cart_button, 
                             {{WRAPPER}} .woocommerce ul.products li.product .button' => 'text-align: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'html_msg',
                    [
                        'type'    => \Elementor\Controls_Manager::RAW_HTML,
                        'raw'     => '<div style="margin:0; background-color: #f7d08a; padding: 10px 15px; border-left: 4px solid #f5a623; color: #6a3403; font-style:normal; "><strong>Read Me First:</strong>1. Incase of some themes, the appearance might be distorted in the editor. However, if you refresh and view the actual page, it should display correctly.<br>2. Some themes might be able to over ride these settings.</div>',
                        'content_classes' => 'elementor-descriptor',
                    ]
                );                 

                // [Add more controls as needed]

                $this->end_controls_section();
            }

protected function render() {
    $settings = $this->get_settings_for_display();

    // Dynamic inline styles based on widget settings
    $dynamic_styles = '<style>';

    if ('yes' !== $settings['show_title']) {
        $dynamic_styles .= '{{WRAPPER}} .woocommerce-loop-product__title { display: none; }';
    }
    if ('yes' !== $settings['show_price']) {
        $dynamic_styles .= '{{WRAPPER}} .woocommerce ul.products li.product .price { display: none; }';
    }
    if ('yes' !== $settings['show_add_to_cart']) {
        $dynamic_styles .= '{{WRAPPER}} .woocommerce ul.products li.product .add_to_cart_button, {{WRAPPER}} .woocommerce ul.products li.product .button { display: none; }';
    }
    if ('yes' === $settings['hide_sale_badge']) {
        $dynamic_styles .= '{{WRAPPER}} .woocommerce ul.products li.product span.onsale { display: none; }';
    }

    $dynamic_styles .= '</style>';
    echo $dynamic_styles;

    // Building the WooCommerce shortcode based on settings
    $columns = intval($settings['columns']);
    $number_of_products = intval($settings['number_of_products']);
    $product_type = $settings['product_type'];

    $shortcode_attributes = [
        'limit' => $number_of_products,
        'columns' => $columns
    ];

    // Append additional attributes based on the product type
    switch ($product_type) {
        case 'best_sellers':
            $shortcode_attributes['best_selling'] = 'true';
            break;
        case 'on_sale':
            $shortcode_attributes['on_sale'] = 'true';
            break;
        case 'featured':
            $shortcode_attributes['visibility'] = 'featured';
            break;
        case 'categories':
            if (!empty($settings['product_categories'])) {
                $shortcode_attributes['category'] = implode(',', $settings['product_categories']);
            }
            break;
        case 'tags':
            if (!empty($settings['product_tags'])) {
                $shortcode_attributes['tag'] = implode(',', $settings['product_tags']);
            }
            break;
        case 'attributes':
            if (!empty($settings['product_attributes']) && !empty($settings['attribute_terms'])) {
                $shortcode_attributes['attribute'] = $settings['product_attributes'];
                $shortcode_attributes['terms'] = implode(',', $settings['attribute_terms']);
            }
            break;
        case 'specific_products':
            if (!empty($settings['selected_products'])) {
                $shortcode_attributes['ids'] = implode(',', $settings['selected_products']);
            }
            break;
    }

    $shortcode = '[products';
    foreach ($shortcode_attributes as $attr => $value) {
        $shortcode .= ' ' . $attr . '="' . esc_attr($value) . '"';
    }
    $shortcode .= ']';

    echo do_shortcode($shortcode); // Execute the WooCommerce shortcode
}
        }

        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new ibtg_WooCommerce_Elementor_Widget());
    }
}

/**
 * Enqueue styles when editing or previewing in Elementor
 */
function ibtg_elementor_widget_styles() {
    if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
        wp_enqueue_style('ibt-woocommerce-elementor-widget-style', plugin_dir_url(dirname(__FILE__)) . 'css/style.css', [], '1.0');
    }
}
add_action('wp_enqueue_scripts', 'ibtg_elementor_widget_styles');

/**
 * AJAX callback to retrieve WooCommerce attribute terms
 */
function ibtg_get_attribute_terms_callback() {
    check_ajax_referer('ibtg_woo_elementor', 'nonce');

    if (isset($_POST['attribute_name'])) {
        $taxonomy = 'pa_' . sanitize_text_field($_POST['attribute_name']);
        $terms = get_terms($taxonomy);

        $response = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $response[$term->slug] = $term->name;
            }
        }

        echo wp_json_encode($response);
    }
    wp_die();
}
add_action('wp_ajax_get_attribute_terms', 'ibtg_get_attribute_terms_callback');
add_action('wp_ajax_nopriv_get_attribute_terms', 'ibtg_get_attribute_terms_callback');
