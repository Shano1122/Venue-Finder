<?php
/**
 * Plugin Name: Venue Finder — Static to Plugin
 * Description: Hero + Filter Bar redesign; Drawer, Venue Cards, Finder unchanged. Drawer opens on click. Pixel-Match drawer CSS.
 * Version: 1.1.2
 * Author: ChatGPT
 * Text Domain: vfs
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VFS_PATH', plugin_dir_path( __FILE__ ) );
define( 'VFS_URL', plugin_dir_url( __FILE__ ) );

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('vfs-fonts','https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Inter:wght@400;500;600&display=swap',[],null);
  wp_enqueue_style('vfs-style', VFS_URL.'public/css/venue-finder.css', [], filemtime(VFS_PATH.'public/css/venue-finder.css'));
  wp_enqueue_script('vfs-js', VFS_URL.'public/js/venue-finder.js', [], filemtime(VFS_PATH.'public/js/venue-finder.js'), true);
  wp_localize_script('vfs-js','vfsData',[ 'ajax_url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('vfs_nonce') ]);
});

add_action('init', function(){
  register_post_type('vf_venue', ['label'=>__('Venues','vfs'),'public'=>true,'has_archive'=>true,'menu_icon'=>'dashicons-building','supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true]);
  register_taxonomy('vf_location','vf_venue', ['label'=>__('Locations','vfs'),'public'=>true,'hierarchical'=>true,'show_in_rest'=>true]);
  register_taxonomy('vf_venue_type','vf_venue', ['label'=>__('Venue Types','vfs'),'public'=>true,'show_in_rest'=>true]);
  register_taxonomy('vf_feature','vf_venue', ['label'=>__('Features','vfs'),'public'=>true,'show_in_rest'=>true]);
  register_taxonomy('vf_style','vf_venue', ['label'=>__('Wedding Styles','vfs'),'public'=>true,'show_in_rest'=>true]);
});

function vfs_render_venue_box( $post ){
  $img = get_the_post_thumbnail_url($post->ID,'large'); if(!$img) $img = VFS_URL.'assets/images/placeholder.jpg';
  $title = get_the_title($post);
  $perma = get_permalink($post);
  $short = esc_html( wp_trim_words( get_the_excerpt($post), 24 ) );
  ob_start(); ?>
  <div class="venue-box">
    <div class="venue-box__grid">
      <div class="venue-box__media"><img loading="lazy" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>"></div>
      <div class="venue-box__body">
        <h2 class="v-title"><?php echo esc_html($title); ?></h2>
        <p class="v-desc"><?php echo $short; ?></p>
        <div class="v-actions"><a class="btn-outline" href="<?php echo esc_url($perma); ?>">View Venue</a></div>
      </div>
    </div>
  </div>
  <?php return ob_get_clean();
}

add_shortcode('venue_finder', function($atts){
  $atts = shortcode_atts(['count'=>6,'hero_title'=>'Northbrook Park','hero_location'=>'Surrey'], $atts,'venue_finder');
  $first = get_posts(['post_type'=>'vf_venue','posts_per_page'=>1,'no_found_rows'=>true]);
  $hero_img = ''; if($first){ $hero_img = get_the_post_thumbnail_url($first[0]->ID,'full'); }
  if(!$hero_img){ $hero_img = VFS_URL.'assets/images/placeholder.jpg'; }

  $q = new WP_Query(['post_type'=>'vf_venue','posts_per_page'=>intval($atts['count'])]);

  ob_start(); ?>
  <!-- ===== HERO (new design) ===== -->
  <section class="hero" role="region" aria-label="Hero">
    <div class="hero-inner">
      <div class="left">
        <h1 class="venue-title"><?php echo esc_html($atts['hero_title']); ?></h1>
        <div class="venue-loc"><?php echo esc_html($atts['hero_location']); ?></div>
        <a href="#" class="btn-outline">Menu detail</a>
        <div class="form" role="search">
          <label for="venue-search">Search Venue:</label>
          <input id="venue-search" class="input" type="text" placeholder="Type at least three letters" autocomplete="off" />
        </div>
      </div>
      <div class="right">
        <img class="photo" loading="lazy" src="<?php echo esc_url($hero_img); ?>" alt="<?php echo esc_attr($atts['hero_title']); ?>">
        <div class="fade-bottom" aria-hidden="true"></div>
      </div>
    </div>
  </section>

  <!-- ===== FILTER BAR (new design) ===== -->
  <section class="filterbar" aria-label="Filter Bar">
    <div class="filter-inner">
      <div class="fb-left">
        <label class="sort-label" for="sortSelect">Sort by:</label>
        <div class="select-wrap">
          <select id="sortSelect" aria-label="Sort venues">
            <option value="inspire" selected>Inspire Me</option>
            <option value="az">A – Z</option>
            <option value="za">Z – A</option>
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
          </select>
          <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </div>
      </div>
      <div class="fb-right">
        <button class="filters-btn" id="openFilters" type="button" aria-controls="filter-drawer" aria-haspopup="dialog">
          <svg class="sliders-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 6h10"/><circle cx="17" cy="6" r="2"/>
            <path d="M3 12h6"/><circle cx="13" cy="12" r="2"/>
            <path d="M3 18h14"/><circle cx="19" cy="18" r="2"/>
          </svg>
          <span>Filters</span>
        </button>
        <a href="#" class="clear-link" id="clearFilters">Clear all filters <span id="filterCount">(0)</span></a>
      </div>
    </div>
  </section>

  <!-- ===== Venue Cards (unchanged) ===== -->
  <section class="venue-panels" id="vfs-venue-list">
    <?php if($q->have_posts()){ while($q->have_posts()){ $q->the_post(); echo vfs_render_venue_box( get_post() ); } wp_reset_postdata(); } else { echo '<p>'.__('No venues yet.','vfs').'</p>'; } ?>
  </section>

  <!-- ===== Finder (updated) ===== -->
  <section class="finder" aria-labelledby="finderEyebrow">
    <div class="finder-inner">
      <div class="finder-copy">
        <div id="finderEyebrow" class="finder-eyebrow">Wedding Venue Finder</div>
        <h3 class="finder-head">Get cracking on your venue hunt with a simple search here, or head straight to the Venue Finder to zone in on the details! The fun starts now.</h3>
      </div>
      <form class="finder-card" id="finderForm" action="#" method="get">
        <div class="frow">
          <label class="sr-only" for="fLocation">Location</label>
          <div class="select-wrap full">
            <select id="fLocation" required>
              <option selected>Location</option>
              <option>London</option>
              <option>Manchester</option>
              <option>Surrey</option>
            </select>
            <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
          </div>
        </div>
        <div class="frow two">
          <div class="select-wrap">
            <label class="sr-only" for="fCapacity">Capacity</label>
            <select id="fCapacity">
              <option selected>Capacity</option>
              <option>0–50</option>
              <option>51–150</option>
              <option>150+</option>
            </select>
            <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <div class="select-wrap">
            <label class="sr-only" for="fType">Venue Type</label>
            <select id="fType">
              <option selected>Venue Type</option>
              <option>Barn</option>
              <option>Hotel</option>
              <option>Garden</option>
            </select>
            <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
          </div>
        </div>
        <div class="frow two">
          <div class="select-wrap">
            <label class="sr-only" for="fFeatures">Features</label>
            <select id="fFeatures">
              <option selected>Features</option>
              <option>Outdoor</option>
              <option>Waterfront</option>
              <option>Historic</option>
            </select>
            <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <div class="select-wrap">
            <label class="sr-only" for="fStyle">Wedding Style</label>
            <select id="fStyle">
              <option selected>Wedding Style</option>
              <option>Classic</option>
              <option>Modern</option>
              <option>Boho</option>
            </select>
            <svg class="chev-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
          </div>
        </div>
        <button class="finder-submit" type="submit">Start Venue Search</button>
      </form>
    </div>
  </section>

  <!-- ===== Drawer (unchanged) ===== -->
  <div id="filter-drawer" class="fdrawer" aria-hidden="true">
    <div class="fdrawer__backdrop" aria-hidden="true"></div>
    <aside class="fdrawer__panel" role="dialog" aria-modal="true" aria-label="Filters">
      <div class="drawer" role="document">
        <div class="hdr">
          <div class="hdr-left">
            <svg class="icon-sliders" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 6h10"/><circle cx="17" cy="6" r="2"/><path d="M21 6h0"/>
              <path d="M3 12h6"/><circle cx="13" cy="12" r="2"/><path d="M21 12h0"/>
              <path d="M3 18h14"/><circle cx="19" cy="18" r="2"/>
            </svg>
            <div class="title">Filter</div>
          </div>
          <a href="#" class="close-link" aria-label="Close">Close</a>
        </div>

        <div class="actions">
          <button class="btn-apply" id="applyBtn">APPLY FILTERS <span id="applyCount">(0)</span></button>
          <div class="clear-wrap"><a href="#" id="clearAll" class="link-clear">Clear All <span id="clearCount">(0)</span></a></div>
        </div>

        <div class="hr"></div>

        <ul class="list">
          <li>
            <div class="row" data-key="location" role="button" aria-expanded="false">
              <div class="label">Location</div>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </div>
            <div class="panel" aria-hidden="true" data-key="location">
              <div class="pill-list">
                <?php
                  $regions = get_terms(['taxonomy'=>'vf_location','hide_empty'=>false,'parent'=>0]);
                  if(!is_wp_error($regions) && $regions){
                    foreach($regions as $t){
                      echo '<button type="button" class="pill" data-term="'.esc_attr($t->slug).'">'.esc_html($t->name).'</button>';
                    }
                  } else { echo '<em>'.__('Add Locations under Venues → Locations.','vfs').'</em>'; }
                ?>
              </div>
            </div>
          </li>
          <li>
            <div class="row" data-key="capacity" role="button" aria-expanded="false">
              <div class="label">Capacity</div>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </div>
            <div class="panel" aria-hidden="true" data-key="capacity">
              <ul class="fchecks">
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="20"><span>Elopement (2–20)</span></label></li>
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="40"><span>Intimate (20–40)</span></label></li>
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="60"><span>Simple (40–60)</span></label></li>
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="150"><span>Party (60–150)</span></label></li>
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="300"><span>Grand (150–300)</span></label></li>
                <li><label class="fcheck"><input type="checkbox" name="capacity[]" value="301"><span>Epic (300+)</span></label></li>
              </ul>
            </div>
          </li>
          <li>
            <div class="row" data-key="venueType" role="button" aria-expanded="false">
              <div class="label">Venue Type</div>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </div>
            <div class="panel" aria-hidden="true" data-key="venueType">
              <ul class="fchecks">
                <?php
                  $types = get_terms(['taxonomy'=>'vf_venue_type','hide_empty'=>false]);
                  if(!is_wp_error($types) && $types){
                    foreach($types as $t){
                      echo '<li><label class="fcheck"><input type="checkbox" name="venueType[]" value="'.esc_attr($t->slug).'"><span>'.esc_html($t->name).'</span></label></li>';
                    }
                  } else { echo '<li><em>'.__('Add Venue Types under Venues → Venue Types.','vfs').'</em></li>'; }
                ?>
              </ul>
            </div>
          </li>
          <li>
            <div class="row" data-key="features" role="button" aria-expanded="false">
              <div class="label">Features</div>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </div>
            <div class="panel" aria-hidden="true" data-key="features">
              <ul class="fchecks">
                <?php
                  $features = get_terms(['taxonomy'=>'vf_feature','hide_empty'=>false]);
                  if(!is_wp_error($features) && $features){
                    foreach($features as $t){
                      echo '<li><label class="fcheck"><input type="checkbox" name="features[]" value="'.esc_attr($t->slug).'"><span>'.esc_html($t->name).'</span></label></li>';
                    }
                  } else { echo '<li><em>'.__('Add Features under Venues → Features.','vfs').'</em></li>'; }
                ?>
              </ul>
            </div>
          </li>
          <li>
            <div class="row" data-key="weddingStyle" role="button" aria-expanded="false">
              <div class="label">Wedding Style</div>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </div>
            <div class="panel" aria-hidden="true" data-key="weddingStyle">
              <ul class="fchecks">
                <?php
                  $styles = get_terms(['taxonomy'=>'vf_style','hide_empty'=>false]);
                  if(!is_wp_error($styles) && $styles){
                    foreach($styles as $t){
                      echo '<li><label class="fcheck"><input type="checkbox" name="style[]" value="'.esc_attr($t->slug).'"><span>'.esc_html($t->name).'</span></label></li>';
                    }
                  } else { echo '<li><em>'.__('Add Styles under Venues → Wedding Styles.','vfs').'</em></li>'; }
                ?>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </aside>
  </div>
  <?php
  return ob_get_clean();
});

add_action('wp_ajax_vfs_filter_venues','vfs_ajax_filter');
add_action('wp_ajax_nopriv_vfs_filter_venues','vfs_ajax_filter');
function vfs_ajax_filter(){
  check_ajax_referer('vfs_nonce');
  $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : [];
  $sort    = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'inspire';

  $args = ['post_type'=>'vf_venue','posts_per_page'=>12];

  switch($sort){
    case 'newest': $args['orderby']='date'; $args['order']='DESC'; break;
    case 'oldest': $args['orderby']='date'; $args['order']='ASC'; break;
    case 'az':     $args['orderby']='title'; $args['order']='ASC'; break;
    case 'za':     $args['orderby']='title'; $args['order']='DESC'; break;
    case 'price_low':  $args['meta_key']='vfs_price'; $args['orderby']='meta_value_num'; $args['order']='ASC'; break;
    case 'price_high': $args['meta_key']='vfs_price'; $args['orderby']='meta_value_num'; $args['order']='DESC'; break;
    default: $args['orderby']='date'; $args['order']='DESC';
  }

  $tax_query = [];
  if(!empty($filters['venueType'])) $tax_query[] = ['taxonomy'=>'vf_venue_type','field'=>'slug','terms'=>(array)$filters['venueType']];
  if(!empty($filters['features']))  $tax_query[] = ['taxonomy'=>'vf_feature','field'=>'slug','terms'=>(array)$filters['features']];
  if(!empty($filters['style']))     $tax_query[] = ['taxonomy'=>'vf_style','field'=>'slug','terms'=>(array)$filters['style']];
  if(!empty($filters['location']))  $tax_query[] = ['taxonomy'=>'vf_location','field'=>'slug','terms'=>(array)$filters['location']];
  if($tax_query) $args['tax_query'] = array_merge(['relation'=>'AND'],$tax_query);

  if(!empty($filters['capacity'])){
    $vals = array_map('intval',(array)$filters['capacity']); $target = max($vals);
    $args['meta_query'] = [ ['key'=>'vfs_cap_max','value'=>$target,'type'=>'NUMERIC','compare'=>'>='] ];
  }

  $q = new WP_Query($args); ob_start();
  if($q->have_posts()){ while($q->have_posts()){ $q->the_post(); echo vfs_render_venue_box( get_post() ); } wp_reset_postdata(); }
  else { echo '<p>'.__('No venues match your filters.','vfs').'</p>'; }
  wp_send_json(['success'=>true,'html'=>ob_get_clean()]);
}
