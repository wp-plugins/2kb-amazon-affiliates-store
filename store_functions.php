<?php

!defined('ABSPATH') and exit;

function getKbAmazonApi() {
    $amz = getKbAmz()->getOption('amazon');
    $accessKey    = isset($amz['accessKey']) ? $amz['accessKey'] : null;
    $secretKey    = isset($amz['secretKey']) ? $amz['secretKey'] : null;
    $country      = isset($amz['country']) ? $amz['country'] : null;
    $associateTag = isset($amz['associateTag']) ? $amz['associateTag'] : null;

    return new KbAmazonApi($accessKey, $secretKey, $country, $associateTag);
}

function hasKbAmazonApiDetails()
{
    $amz = getKbAmz()->getOption('amazon');
    $accessKey    = isset($amz['accessKey']) ? $amz['accessKey'] : null;
    $secretKey    = isset($amz['secretKey']) ? $amz['secretKey'] : null;
    $country      = isset($amz['country']) ? $amz['country'] : null;
    $associateTag = isset($amz['associateTag']) ? $amz['associateTag'] : null;
    return $accessKey && $secretKey && $country && $associateTag;
}

function getKbAdminUser() {
    global $wpdb;
    $users = get_users('role=administrator');
    return isset($users[0]) ? $users[0] : null;
}

function getKbPluginUrl($append = null)
{
    return plugins_url($append, __FILE__ );// . ($append ? '/' : '') . $append;
    // return get_site_url() . '/wp-content/plugins/' . KbAmazonStoreFolderName . ($append ? '/' : '') . $append;
}

function getKbPostVar($name, $default = null)
{
        $options = $_POST;
        $result = null;
        if (strpos($name, '.') === false) {
            $result = isset($options[$name])
                   && !empty($options[$name])
                   ? $options[$name] : null;
        } else {
            $paths = explode('.', $name);
            $value = null;
            $values = $options;
            foreach ($paths as $key => $path) {
                if (isset($values[$path])) {
                    $value = $values[$path];
                    if (is_array($value)) {
                        $values = $values[$path];
                    }
                    unset($paths[$key]);
                }
            }
            if (count($paths) == 0) {
                $result = $value;
            }
        }
    return $result === null ? $default : $result;
}

function kbAmzAdminMessage($msg, $type = 'alert-success')
{
    return sprintf(
        '<div class="alert %s">%s</div>',
        $type,
        $msg
    );
}

function kbGetUrlWithParams($params = array(), $useGetUrl = true)
{
    $current = isset($params['BASE_URL']) ? $params['BASE_URL'] : $_SERVER['REQUEST_URI'];
    if (isset($params['BASE_URL'])) {
        unset($params['BASE_URL']);
    }
    $arr = explode('?', $current);
    $baseUrl = $arr[0];
    $merged = !empty($_GET) && $useGetUrl ? $_GET : array();
    foreach ($params as $key => $param) {
        $merged[$key] = $param;
    }
    return $baseUrl . '?' . http_build_query($merged);
}

function kbAmzSelect($vars, $current = null, $addEmpty = false)
{
    $str = '';
    if (false !== $addEmpty) {
        $str .= sprintf(
            '<option value="">%s</option>',
            $addEmpty
        );
    }
    
    foreach ($vars as $val => $label) {
        if (is_array($label)) {
            $str .= '<optgroup label="'.$val.'">';
                foreach ($label as $sub => $subVal) {
                    $str .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        $sub,
                        $sub == $current ? 'selected="selected"' : '',
                        $subVal
                    );
                }
            $str .= '</optgroup>';
        } else {
            $str .= sprintf(
                '<option value="%s" %s>%s</option>',
                $val,
                $val == $current ? 'selected="selected"' : '',
                $label
            );
        }
    }
    return $str;
}

function kbMergeUnique($arry1, $array2)
{
    foreach($array2 as $val) {
        if (!in_array($val, $arry1)) {
            $arry1[] = $val;
        }
    }
    return $arry1;
}

function kbAmzFilterAttributes(&$attributes)
{
    $excluded = getKbAmz()->getOption('excludedAttributes', array());
    foreach ($excluded as $attr) {
        unset($attributes[$attr]);
    }
    
    $attributes = apply_filters('kbAmzFilterAttributes', $attributes);
}

function getKbCronFirstInterval()
{
    foreach (wp_get_schedules() as $timeStr => $opts) {
        return $timeStr;
    }
}

function getKbHostname()
{
    $parts = parse_url(get_site_url());
    return str_replace('www.', '', $parts['host']);
}

function kbAmzIsMainQuery($bool = null){
    static $is;
    if (!is_null($bool)){
        $is = $bool;
    }
    return $is;
}

function kbAmzIsSimilarQuery($bool = null){
    static $is;
    if (!is_null($bool)){
        $is = $bool;
    }
    return $is;
}


function kbIsHomePage()
{
    return is_front_page() || is_home();
}

function kbAmzNavigation($maxNumPages = 0) {

	// Don't print empty markup if there's only one page.
	if ($GLOBALS['wp_query']->max_num_pages < 2 && $maxNumPages < 2) {
		return;
	}
        $maxNumPages = $GLOBALS['wp_query']->max_num_pages
                     ? $GLOBALS['wp_query']->max_num_pages : $maxNumPages;

        $paged = getKbAmzPaged();
        
	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$query_args   = array();
	$url_parts    = explode( '?', $pagenum_link );

	if ( isset( $url_parts[1] ) ) {
		wp_parse_str( $url_parts[1], $query_args );
	}

	$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
	$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

	$format  = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format .= $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';

	// Set up paginated links.
	$links = paginate_links( array(
		'base'     => $pagenum_link,
		'format'   => $format,
		'total'    => $maxNumPages,
		'current'  => $paged,
		'mid_size' => 5,
                'end_size' => 5,
		'add_args' => array_map( 'urlencode', $query_args ),
		'prev_text' => __( '&larr; Previous', 'twentyfourteen' ),
		'next_text' => __( 'Next &rarr;', 'twentyfourteen' ),
	) );

	if ( $links ) :

	?>
	<nav class="navigation paging-navigation kb-pagination" role="navigation">
            <div class="loop-pagination links">
                    <?php echo $links; ?>
            </div>
	</nav>
	<?php
	endif;
}


function hasKbPostShortCode($code)
{
    $post = getKbAmzCurrentPost();
    $shortCode = getKbAmz()->getShortCode($code, true);
    return $post && has_shortcode($post->post_content, $shortCode);
}

function getKbAmzPaged()
{
    $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
    if ($paged == 1) {
        $paged = get_query_var( 'page' );
    }
    if (isset($_GET['paged']) && $paged = 1) {
        $paged = intval($_GET['paged']);
    }
    return empty($paged) ? 1 : $paged;
}

function kbAmzTheBreadCrumb() {

    $sep = ' » ';

    if (!is_front_page()) {
        echo '<div class="breadcrumb">';
        echo '<a href="';
        echo get_option('home');
        echo '">';
        bloginfo('name');
        echo '</a>';

        if (is_category()){
            echo $sep;
            $str =  get_category_parents(getKbAmz()->getCurrentCategory(), true, ' &raquo; ' );
            echo substr($str, 0, strlen($str) - 8);
        } elseif (is_archive() || is_single()){
            if ( is_day() ) {
                echo $sep;
                printf( __( '%s', 'text_domain' ), get_the_date() );
            } elseif ( is_month() ) {
                echo $sep;
                printf( __( '%s', 'text_domain' ), get_the_date( _x( 'F Y', 'monthly archives date format', 'text_domain' ) ) );
            } elseif ( is_year() ) {
                echo $sep;
                printf( __( '%s', 'text_domain' ), get_the_date( _x( 'Y', 'yearly archives date format', 'text_domain' ) ) );
            } else {
                //_e( 'Blog Archives', 'text_domain' );
            }
        }

        if (is_single()) {
            echo $sep;
            the_title();
        }

        if (is_page()) {
            echo $sep;
            echo the_title();
        }

        if (is_home()){
            global $post;
            $page_for_posts_id = get_option('page_for_posts');
            if ( $page_for_posts_id ) { 
                $post = get_page($page_for_posts_id);
                setup_postdata($post);
                the_title();
                rewind_posts();
            }
        }
        $sQuery = get_search_query();
        if (!empty($sQuery)) {
            echo $sep;
            echo 'Search Results for: ' . $sQuery;
        }

        echo '</div>';
    }
}
function getKbAmzCurrentPostId()
{
    $p = get_query_var('p');
    
    if (!$p) {
        $p = url_to_postid($_SERVER['REQUEST_URI']);
    }
    return $p;
}

function getKbAmzCurrentPost()
{
    $id = getKbAmzCurrentPostId();
    $post = null;
    if ($id) {
        $post = get_post($id);
        wp_reset_query(); 
    }
    return $post;
}

function getKbAmzAjaxUrl()
{
    return admin_url('admin-ajax.php');
    //return get_site_url() . '/wp-admin/admin-ajax.php';
}

function getKbAmzProductTopCategory()
{
    $cats = get_the_category();
    if (isset($cats[0])) {
        return $cats[0];
    }
}

function getKbAmzProductBottomCategory()
{
    $cats = get_the_category();
    foreach ($cats as $key => $cat) {
        if ($cat->term_id == 1) {
            unset($cats[$key]);
            break;
        }
    }
    reset($cats);
    if (isset($cats[count($cats)-1])) {
        return $cats[count($cats)-1];
    }
}

function getKbAmzExcerptFromContent($postcontent, $length)
{
    $this_excerpt = strip_shortcodes( $postcontent );
    $this_excerpt = strip_tags($this_excerpt);
    $this_excerpt = substr($this_excerpt, 0, $length);
    
    $parts = preg_split('/([\s\n\r]+)/', $this_excerpt, null, PREG_SPLIT_DELIM_CAPTURE);
    $parts_count = count($parts);

    $length = 0;
    $last_part = 0;
    for (; $last_part < $parts_count; ++$last_part) {
      $length += strlen($parts[$last_part]);
      if ($length > $length) { break; }
    }

    return implode(array_slice($parts, 0, $last_part));
}

function kbAmzBootstrapPagination($count, $perPage)
{
    $query = $_GET;

    $curPage = isset($_GET['kbpage']) ? $_GET['kbpage'] : 1;
    $data = array();
    if($count){
        $pages = ceil($count / $perPage);
        if($pages > 1){

            $page = $curPage == 1 ? 1 : $curPage - 1;
            $query['kbpage'] = $page;
            $data[] = \sprintf(
                '<li%s><a href="?%s">%s</a></li>' . PHP_EOL,
                $curPage == 1 ? ' class="disabled"' : '',
                http_build_query($query),
                '&laquo;'
            );
            $query['kbpage'] = 1;
            $data[] = \sprintf(
                '<li%s><a href="?%s">%s</a></li>' . PHP_EOL,
                $curPage == 1 ? ' class="disabled first"' : ' class="first"',
                http_build_query($query),
                __('First')
            );

            for($i = 1; $i <= $pages; $i++) {
                $canAdd = false;
                if ($i >= $curPage - 5 && $i <= $curPage + 5) {
                    $canAdd = true;
                }

                if (!$canAdd) {
                    continue;
                }
                
                $query['kbpage'] = $i;
                $data[] = \sprintf(
                    '<li%s><a href="?%s">%s</a></li>' . PHP_EOL,
                    $i == $curPage ? ' class="active"' : '',
                     http_build_query($query),
                    $i
                );  


            }
            
            $query['kbpage'] = $pages;
            $data[] = \sprintf(
                '<li%s><a href="?%s">%s</a></li>' . PHP_EOL,
                $curPage == $pages ? ' class="disabled last"' : ' class="last"',
                http_build_query($query),
                __('Last')
            );

            $page = $curPage == $pages ? $pages : $curPage + 1;
            $query['kbpage'] = $page;
            $data[] = \sprintf(
                '<li%s><a href="?%s">%s</a></li>' . PHP_EOL,
                $curPage == $pages ? ' class="disabled"' : '',
                http_build_query($query),
                '&raquo;'
            );
        }
    }
    
    if (!empty($data)) {
        return \sprintf(
            '<ul class="pagination">%s</ul>' . PHP_EOL,
            join('', $data)
        ); 
    }
}

function kbAmzHiddenInput()
{
    if (!empty($_GET)) {
        $data = array();
        foreach ($_GET as $name => $val) {
            $data[] = sprintf(
                '<input type="hidden" name="%s" value="%s"/>',
                $name,
                $val
            );
        }
        return implode(PHP_EOL, $data);
    }
}
function kbAmzSortOrder($a, $b)
{
    return $a['order'] - $b['order'];
}

function kbAmzGetArrayFromFlatten($array, $hasKey)
{
    $output = array();
    foreach ($array as $key => $val) {
        if (substr($key, 0, strlen($hasKey)) == $hasKey) {
            $loc = &$output;
            foreach(explode('.', $key) as $step)
            {
              $loc = &$loc[$step];
            }
            $loc = $val;
        }
    }
    
    return $output;
}

function getKbAmzStoreHealth()
{
    $hours = 0;
    $count = getKbAmz()->getProductsToUpdateCount();
    if ($count > 0) {
        $per = getKbAmz()->getOption('updateProductsPriceCronNumberToProcess');
        $interval = getKbAmz()->getOption('updateProductsPriceCronInterval');
        $intervals = $count / $per;
        $intervalHours = 1;
        if ($interval == 'twicedaily') {
            $intervalHours = 12;
        } else if ($interval == 'daily') {
            $intervalHours = 24;
        }
        $hours = ceil($intervals . $intervalHours);
    }
    
    $h = round(($hours / 24) * 100);
    return $h;
}

function getKbAmzStoreHealthHtml()
{
    $health = getKbAmzStoreHealth();
    $class = 'danger';
    if ($health > 80 && $health <= 100) {
        $class = 'success';
    } else if ($health > 50 && $health <= 100) {
        $class = 'warning';
    }
    $percent = $health > 100 ? 100 : $health;
    ob_start();
    ?>
    <div class="progress">
        <div class="progress-bar progress-bar-<?php echo $class;?>" style="width: <?php echo $percent; ?>%;">
          <?php echo $health; ?>%
        </div>
    </div>
    <?php
    return ob_get_clean();
}