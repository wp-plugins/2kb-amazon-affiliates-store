<?php

class KbAmzWidget extends WP_Widget
{
    protected static $kbWidgets     = array();
    protected static $kbFilterUsed  = array();

    public function __construct($id_base, $name, $widget_options = array(), $control_options = array()) {
        parent::__construct($id_base, $name, $widget_options, $control_options);
        $key = get_class($this);
        if (!isset(self::$kbWidgets[$key])) {
            self::$kbWidgets[$key] = $this;
        }
    }


    public function update($new, $old)
    {
        $instance = array();
        $instance['title'] = (!empty($new['title']) ) ? strip_tags($new['title']) : '';
        $instance['meta_key'] = (!empty($new['meta_key']) ) ? strip_tags($new['meta_key']) : '';
        $instance['category'] = (!empty($new['category']) ) ? strip_tags($new['category']) : 0;
        $instance['page'] = (!empty($new['page']) ) ? strip_tags($new['page']) : 0;
        
        return $instance;
    }
    
    public function form($instance)
    {
        $title = isset($instance['title']) ? $instance['title'] :  __('New title', 'text_domain');
        $metaKey = isset($instance['meta_key']) ? $instance['meta_key'] : null;
        $category = isset($instance['category']) ? $instance['category'] : 0;
        $page = isset($instance['page']) ? $instance['page'] : 0;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php if (!isset($instance['disableMeta']) || !$instance['disableMeta']) :?>
        <p>
            <label for="<?php echo $this->get_field_id('meta_key'); ?>"><?php _e('Meta Key:'); ?></label> 
            <select id="<?php echo $this->get_field_id('meta_key'); ?>" name="<?php echo $this->get_field_name('meta_key'); ?>" type="text" value="<?php echo esc_attr($metaKey); ?>" style="max-width: 100%;">
                <?php foreach (getKbAmz()->getAttributes() as $name => $type) : ?>
                <option value="<?php echo $name; ?>"<?php echo $metaKey == $name ? 'selected="selected"' : ''?>><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php endif; ?>
        <p>
            <label for="<?php echo $this->get_field_name('category');?>"><?php _e('Active Only For Selected Category (Stack with Page):'); ?></label> 
            <?php wp_dropdown_categories(array(
                'orderby' => 'name',
                'tab_index' => true,
                'hierarchical' => true,
                'show_count' => true,
                'class' =>'kb-amz-select-width-100',
                'name' => $this->get_field_name('category'),
                'selected' => $category,
                'show_option_all'    => __('Use For All Categories')
            )); ?>
        </p>
        <p>
            <label for="<?php echo $this->get_field_name('category');?>"><?php _e('Active Only For Selected Page (Stack with Category):'); ?></label> 
            <?php wp_dropdown_pages(array(
                'orderby' => 'name',
                'class' =>'kb-amz-select-width-100',
                'name' => $this->get_field_name('page'),
                'selected' => $page,
                'show_option_none'    => __('Use For All Pages')
            )); ?>
        </p>
        <?php
    } 
    
    protected function getKeyParam($name = '', $number = false)
    {
        return strtolower($this->id_base.($number ? $number : $this->number).$name);
    }
    
    public static function getWidgets()
    {
        return self::$kbWidgets;
    }
    
    public static function getWidgetsFilters()
    {
        $where = array();
        foreach (self::getWidgets() as $widget) {
            if (method_exists($widget, 'getWidgetQueryFilter')) {
                $str = $widget->getWidgetQueryFilter();
                if ($str) {
                    $where[] = $str;
                }
            }
        }
        return $where;
    }
    
    protected function setFilterKeyUsed($key)
    {
        
        self::$kbFilterUsed[$key] = true;
    }
    
    public function getFilterKeyUsed($key)
    {
        return isset(self::$kbFilterUsed[$key]) && self::$kbFilterUsed[$key];
    }
    
    public function isKbAllowedToShow($instance)
    {
        if (isset($instance['page']) && !empty($instance['page'])) {
            if (!is_page($instance['page'])) {
                return false;
            }
        }
        
        if (isset($instance['category'])
        && !empty($instance['category'])
        && !getKbAmz()->isCurrentCategory($instance['category'])) {
            return false;
        }
        
        if (isset($instance['categoryAndPageOnly']) && $instance['categoryAndPageOnly']) {
            return true;
        }
        
        if ((is_single() || is_page())) {
            if (hasKbPostShortCode('listProduct')
            && !getKbAmz()->isPostProduct(get_the_ID())) {
                return true;
            }
            return false;
        }
        
        return true;
    }
}


class kbAmazonShomProductCategories extends KbAmzWidget
{

    function __construct() {

        parent::__construct(
                'kbAmazonShomProductCategories', 'Kb Product Categories', array()
        );
        
        getKbAmz()->addWidgetsType('productCategories', array(
            'label' => 'Product Categories',
            'description' => __('Show Product Categories with products count.')
        ));
    }

    public function widget($args, $instance) {
        $instance['categoryAndPageOnly'] = true;
        if (!$this->isKbAllowedToShow($instance)) {
            return;
        }
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];


        $cats = get_categories(
                array(
                    'orderby' => isset($instance['orderby']) ? $instance['orderby'] : 'count',
                    'order' => 'asc',
                    'number' =>  isset($instance['number']) ? $instance['number'] : 10,
                    'child_of' => isset($instance['child_of']) ? $instance['child_of'] : 0,
                )
        );

        $categories = array();
        echo '<ul class="kb-amz-product-categories">';
        foreach ($cats as $cat) {
            if (!in_array($cat->name, $categories)) {
                echo '<li>';
                echo sprintf(
                    '<a href="%s">%s <b>(%s)</b></a>',
                    get_category_link($cat),
                    $cat->name,
                    $cat->count
                );
                echo '</li>';
                $categories[] = $cat->name;
            }
        }
        echo '</ul>';
        
        echo $args['after_widget'];
    }

    public function form($instance)
    {
       $instance['disableMeta'] = true;
       parent::form($instance);
       $childOf = isset($instance['child_of']) ? $instance['child_of'] : '';
       $orderBy = isset($instance['orderby']) ? $instance['orderby'] : 'count';
       $number = isset($instance['number']) ? $instance['number'] : 10;

        ?>
        <p>
            <label for="<?php echo $this->get_field_name('child_of');?>"><?php _e('Chield Of'); ?></label> 
            <?php wp_dropdown_categories(array(
                'orderby' => 'name',
                'tab_index' => true,
                'hierarchical' => true,
                'show_count' => true,
                'class' =>'kb-amz-select-width-100',
                'name' => $this->get_field_name('child_of'),
                'selected' => $childOf,
                'show_option_all'    => ' - '
            )); ?>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Order By'); ?></label> 
            <select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" type="text" value="<?php echo esc_attr($orderBy); ?>" style="width: 100%;">
                <?php foreach (array('count' => __('Count'), 'name' => __('Name')) as $name => $type) : ?>
                <option value="<?php echo $name; ?>"<?php echo $orderBy == $name ? 'selected="selected"' : ''?>><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number Of Items:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo esc_attr($number); ?>">
        </p>
        <?php
       
       
    }


    public function update($new, $old) {
        $instace =  parent::update($new, $old);
        $instace['child_of'] = (!empty($new['child_of']) ) ? strip_tags($new['child_of']) : 0;
        $instace['orderby'] = (!empty($new['orderby']) ) ? strip_tags($new['orderby']) : 'count';
        $instace['number'] = (!empty($new['number']) ) ? strip_tags($new['number']) : 10;
        return $instace;
    }

}

function kbAmazonShopProductCategoriesLoadWidget() {
    register_widget('kbAmazonShomProductCategories');
}

add_action('widgets_init', 'kbAmazonShopProductCategoriesLoadWidget');


/*
 ******************************************************************************
 */


class kbAmazonShopSliderWidget extends KbAmzWidget
{

    function __construct() {
        parent::__construct(
                'kbamzsldr', // Base ID
                __('Kb Shop Slider'), // Name
                array('description' => __('Creates number slider for given meta key.'),) // Args
        );
         
        wp_enqueue_style(
            'KbAmzVendorSliderCss',
            getKbPluginUrl() . '/template/vendors/slider/css/slider.css'
        );
        
        wp_enqueue_script(
            'KbAmzStoreJs',
            getKbPluginUrl() . '/template/vendors/slider/js/bootstrap-slider.js',
            array('jquery')
        );
        
        add_action('pre_get_posts', array($this, 'filterQuery'));
        
        getKbAmz()->addWidgetsType('slider', array(
            'label' => 'Slider',
            'description' => __('This widget will get the lowest and hightest prices for all products and create slider.')
        ));
    }

    public function widget($args, $instance)
    {
        if (!$this->isKbAllowedToShow($instance)) {
            return;
        }
        
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];
        
        $minKey = $this->getKeyParam('min');
        $maxKey = $this->getKeyParam('max');
        
        $id = uniqid('kb-slider-');
        $url = kbGetUrlWithParams(array($minKey => '__MIN__', $maxKey => '__MAX__'));
        
        $min = !isset($_GET[$minKey]) ? '' : (int) $_GET[$minKey];
        $max = !isset($_GET[$maxKey]) ? '' : (int) $_GET[$maxKey];

        $minMax = getKbAmz()->getMinMaxMetaWidgetMethod(
            $instance['meta_key']
        );

        $dbMin = $minMax[0];
        $dbMax = $minMax[1];
        
        $min = $dbMin > $min || $min == '' ? $dbMin : $min;
        $max = $max > $dbMax || $max == '' ? $dbMax : $max;
        
        $min = floor($min);
        $max = ceil($max);
        ?>
        <div class="kb-amz-price-slider-min-max <?php echo $id;?>"><b><?php echo $min; ?></b> <b class="float-right"><?php echo $max; ?></b></div>
        <input type="text" class="span2 kb-amz-price-slider-element" value="" data-slider-min="<?php echo $dbMin; ?>" data-slider-max="<?php echo $dbMax; ?>" data-slider-step="<?php echo ceil(($dbMin - $dbMax) / 20); ?>" data-slider-value="[<?php echo $min; ?>,<?php echo $max; ?>]" id="<?php echo $id; ?>" />
        <script>
            (function($, id, url){
                $(function(){
                    $('#' + id).slider().on('slideStop', function (e) {
                        var data = $(this).data('value');
                        url = url.replace('__MIN__', parseInt(data[0])).replace('__MAX__', parseInt(data[1]));
                        document.location.href = url;
                    }).on('slide', function (e) {
                        var data = e.value;
                        $('.' + id).find('b:first').html(parseInt(data[0]));
                        $('.' + id).find('b:last').html(parseInt(data[1]));
                    });
                });
            })(jQuery, '<?php echo $id; ?>', '<?php echo $url; ?>');
        </script>
        <?php
        
        echo $args['after_widget'];
    }

    public function filterQuery(WP_Query $query)
    {
        // isKbAmzQuery
        if (!$query->is_main_query() && !kbAmzIsMainQuery()) {
            return;
        }

        if ($query->is_category()
        || $query->is_front_page()
        || $query->is_search() || kbAmzIsMainQuery()) {
            
            $settings = $this->get_settings();
            foreach ($settings as $baseKey => $args) {

                $minKey = $this->getKeyParam('min', $baseKey);
                $maxKey = $this->getKeyParam('max', $baseKey);

                if (isset($_GET[$minKey]) && isset($_GET[$maxKey])) {
                    $key = $args['meta_key'];
                    $min = (int) $_GET[$minKey];
                    $max = (int) $_GET[$maxKey];
                    $meta = $query->get('meta_query');
                    if (empty($meta)) {
                        $meta = array();
                    }
                    $settings = $this->get_settings();
                    $meta[] = array(
                        'key' => $key,
                        'value' => array($min, $max),
                        'type' => 'numeric',
                        'compare' => 'BETWEEN'
                    );
                    $query->set('meta_query', $meta);
                }
            }
        }
    }
    
    public function getWidgetQueryFilter()
    {
        global $wpdb;
        $settings = $this->get_settings();
        $data = array('join' => array(), 'where' => array());
        
        foreach ($settings as $baseKey => $args) {
            
            $minKey = $this->getKeyParam('min', $baseKey);
            $maxKey = $this->getKeyParam('max', $baseKey);
            
            if (isset($_GET[$minKey]) && isset($_GET[$maxKey])) {
                $key = $args['meta_key'];
                $min = (int) $_GET[$minKey];
                $max = (int) $_GET[$maxKey];

                $dbkey = uniqid('nm');
                $data['join'][] =  "JOIN $wpdb->postmeta AS $dbkey ON $dbkey.post_id = t.post_id";
                $data['where'][] = "$dbkey.meta_key = '$key' AND $dbkey.meta_value BETWEEN $min AND $max";
            }
        }
        return $data;
    }

    public function form($instance)
    {
        parent::form($instance);
    }

    public function update($new, $old) {
        return parent::update($new, $old);
    }

}
function kbAmazonShopSliderWidgetLoadWidget() {
    register_widget('kbAmazonShopSliderWidget');
}

add_action('widgets_init', 'kbAmazonShopSliderWidgetLoadWidget');


/*
 ******************************************************************************
 */


class kbAmazonShopAttributeCount extends KbAmzWidget
{

    function __construct() {
        parent::__construct(
                'kbamzattr', // Base ID
                __('Kb Shop Attribute Count'), // Name
                array('description' => __('Creates list with attributes.'),) // Args
        );
        
        add_action('pre_get_posts', array($this, 'filterQuery'));
        
        getKbAmz()->addWidgetsType('attributes', array(
            'label' => 'Attributes',
            'description' => __('This widget will group the selected attribute and create filter link on it.')
        ));
    }

    public function widget($args, $instance) {
        if (!$this->isKbAllowedToShow($instance)) {
            return;
        }
        
        $title = apply_filters('widget_title', $instance['title']);

        $key = $this->getKeyParam();

        $items = getKbAmz()->getMetaCountListWidgetMethod(
            $instance['meta_key'],
            (isset($instance['number']) ? $instance['number'] : null),
            (isset($instance['order']) ? $instance['order'] : null)
        );

        if (!empty($items)) {
            echo $args['before_widget'];
            if (!empty($title))
                echo $args['before_title'] . $title . $args['after_title'];
            echo '<ul>';
            foreach ($items as $item) {
                $classes = array();
                if (isset($_GET[$key]) && $_GET[$key] == $item->meta_id) {
                    $classes[] = 'active';
                }
                if ($item->count == 0) {
                    $classes[] = 'empty';
                }
                echo sprintf(
                    '<li class="cat-item kb-store-attribute %s"><a href="%s" title="%s">%s <span class="kb-store-attribute-count">(%s)</span></a></li>',
                    implode(' ', $classes),
                    kbGetUrlWithParams(array($key => $item->meta_id)),
                    $item->meta_value,
                    $item->meta_value,
                    $item->count
                );
            }
            echo '</ul>';
            echo $args['after_widget'];
        }
    }

    public function getWidgetQueryFilter()
    {
        global $wpdb;
        $base = strtolower($this->id_base);
        $baseLength = strlen($base);
        $data = array('join' => array(), 'where' => array());
        if (!empty($_GET)) {
            foreach ($_GET as $name => $metaId) {
                if (substr($name, 0, $baseLength) == $base) {
                    $row = getKbAmz()->getMetaDataById($metaId);
                    if ($row) {
                        $dbkey = uniqid('nm');
                        $data['join'][] = "JOIN $wpdb->postmeta AS $dbkey ON $dbkey.post_id = t.post_id";
                        $data['where'][] = "$dbkey.meta_key = '$row->meta_key' AND $dbkey.meta_value = '$row->meta_value'";
                    }
                }
            }
        }
        return $data;
    }
    
    public function filterQuery(WP_Query $query)
    {
        // isKbAmzQuery
        if (!$query->is_main_query() && !kbAmzIsMainQuery()) {
            return;
        }
        
        if ($query->is_category()
        || $query->is_front_page()
        || $query->is_search() || kbAmzIsMainQuery()) {
            
            $base = strtolower($this->id_base);
            $baseLength = strlen($base);
            
            if (!empty($_GET)) {
                foreach ($_GET as $name => $metaId) {
                    if (substr($name, 0, $baseLength) == $base) {
                        $row = getKbAmz()->getMetaDataById($metaId);
                        if ($row) {
                            $meta = $query->get('meta_query');
                            if (empty($meta)) {
                                $meta = array();
                            }
                            $meta[] = array(
                                'key' => $row->meta_key,
                                'value' => $row->meta_value,
                                'compare' => '='
                            );
                            $query->set('meta_query', $meta);
                        }
                    }
                }
            }
        }
    }

    public function form($instance)
    {
        parent::form($instance);
        $number = isset($instance['number']) ? $instance['number'] : 6;
        $order = isset($instance['order']) ? $instance['order'] : null;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number Of Items:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo esc_attr($number); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Order:'); ?></label> 
            <select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>" type="text" value="<?php echo esc_attr($order); ?>" style="width: 100%;">
                <?php foreach (array('count' => __('Count'), 'name' => __('Name')) as $name => $type) : ?>
                <option value="<?php echo $name; ?>"<?php echo $order == $name ? 'selected="selected"' : ''?>><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function update($new, $old) {
        $instace =  parent::update($new, $old);
        $instace['order'] = $new['order'];
        $instace['number'] = $new['number'];
        return $instace;
    }

}
function kbAmazonShopAttributeCountLoadWidget() {
    register_widget('kbAmazonShopAttributeCount');
}

add_action('widgets_init', 'kbAmazonShopAttributeCountLoadWidget');