<?php
/*
Plugin Name:		WPMU LinkManager
Description:		You works with a multisite and want manage your outgoing footer links into the themes of each blog? With this plugin you can manage into network admin your outgoing links for every registered blog into your multisite installation.
Version:			1.0.4
Require:            3.4.2
Tested:             5.1
Author:				WPler
Author URI:			https://www.wpler.com/linkmanager-fur-multisites/
Text Domain:		wplerlm
Domain Path:		/i18n/
License:			GPLv2
*/

define( 'WPLER_LM_PATH', !defined( 'WPLER_LM_PATH' ) ? plugin_dir_path( __FILE__ ) : WPLER_LM_PATH );
define( 'WPLER_LM_URL', !defined( 'WPLER_LM_URL' ) ? plugin_dir_url( __FILE__ ) : WPLER_LM_URL );
define( 'WPLER_LM_SLUG', !defined( 'WPLER_LM_SLUG' ) ? plugin_basename( __FILE__ ) : WPLER_LM_SLUG );

// Activation and Deactivation Hooks
register_activation_hook( plugin_basename( __FILE__ ), array( 'wplerlm', 'activation_hook' ) );
register_deactivation_hook( plugin_basename( __FILE__ ), array( 'wplerlm', 'deactivation_hook' ) );

// Starts the plugin class
add_filter( 'admin_init', array( 'wplerlm', 'get_object' ) );
$wplerlm = wplerlm::get_object();
add_action( 'network_admin_menu', array( $wplerlm, 'add_menu' ) );
add_shortcode( 'wplerlm', array( $wplerlm, 'print_link' ) );
/**
 * This function are a wrapper for the shortcode to print out the links by a
 * blog. To use it, writes <?php wplerlm_print_link(); ?> into the footer.php
 * on this place where the link(s) should be printed.
 */
function wplerlm_print_link() {
	do_shortcode( '[wplerlm]' );
}
/**
 * Class wplerlm
 *
 * @package 	wpler
 * @subpackage	linkmanager
 * @author		WPler <plugins@wpler.com>
 * @version		1.0
 * @copyright	2012 WPler <http://www.wpler.com>
 */
class wplerlm {
	/**
	 * Statically Object
	 * @staticvar object
	 * @access private
	 */
	static private $_object = NULL;
	/**
	 * The TextDomain of this plugin
	 * @staticvar string
	 * @access private
	 */
	static private $_textDomain = NULL;
	/**
	 * Returned an instance of this object
	 *
	 * @access public
	 * @static
	 * @since 1.0
	 * @return object
	 */
	static public function get_object() {
		if ( is_null( self::$_object ) )
			self::$_object = new self();
		self::$_textDomain = self::$_object->get_header( 'TextDomain' );
		return self::$_object;
	}
	/**
	 * The constructor of the class will hook the options-page and the
	 * shortcodes and localize this plugin
	 *
	 * @access public
	 * @since 1.0
	 * @uses add_filter,
	 * @return void
	 */
	public function __construct() {
		$path = substr( WPLER_LM_SLUG, 0, strrpos( WPLER_LM_SLUG, '/' ) );
		load_plugin_textdomain( wplerlm::get_textdomain(), FALSE, $path . $this->get_header( 'DomainPath' ) );
	}
	/**
	 * Add a menu item into the network-admin backend for the network-manager
	 *
	 * @access public
	 * @since 1.0
	 * @uses add_menu_page,
	 * @return void
	 */
	public function add_menu() {
		add_menu_page( __( 'WPler LinkManager', wplerlm::get_textdomain() ), __( 'LinkManager', wplerlm::get_textdomain() ), 'manage_network_options', WPLER_LM_SLUG, array( $this, 'links_page' ), 'dashicons-networking' );
		$hook = add_submenu_page( WPLER_LM_SLUG, __( 'WPler LinkManager', wplerlm::get_textdomain() ), __( 'Links', wplerlm::get_textdomain() ), 'manage_network_options', WPLER_LM_SLUG, array( $this, 'links_page' ) );
		add_submenu_page( WPLER_LM_SLUG, __( 'WPler New Link', wplerlm::get_textdomain() ), __( 'New Link', wplerlm::get_textdomain() ), 'manage_network_options', 'wplerlm-newlink', array( $this, 'save_link_page' ) );
		if ($hook) {
		    add_action('load-'.$hook, array($this,'screenHelp'));
        }
	}
	/**
	 * The settings help tabs
     * @since 1.0.4
     * @uses get_current_screen, current_user_can, esc_html,
     * @return false
	 */
	public function screenHelp() {
	    $current_screen = get_current_screen();
	    if (current_user_can('manage_network_options')) {
	        $current_screen->add_help_tab(array(
	            'id'        => 'overview',
                'title'     => __('Help', wplerlm::get_textdomain()),
                'content'   => '<p>'.__('To display your links in the blogs on this network use the follow function on the place where the link should shown:',wplerlm::get_textdomain()).'<br><br>'.
                               '<code>&lt;?php echo do_shortcode(&quot;[wplerlm]&quot;); ?&gt;</code></p>'.
                               '<p>'.__('Place this PHP function on the childtheme template file (i.e. footer.php), where the link should shown. The shortcode identify the blog id and get the correct link, which you have saved for the blog.', wplerlm::get_textdomain()).'</p>'
            ));
        }
    }
	/**
	 * Add the Table of Link Contents to backend into network
	 *
	 * @access public
	 * @since 1.0
	 * @uses _e, get_blog_details, absint, esc_url, esc_attr, $wpdb,
	 * @return void
	 */
	public function links_page() {
		if ( !current_user_can( 'manage_network_options' ) )
			wpdie( __( 'Only the network admin can do anything here.', wplerlm::get_textdomain() ) );
		global $wpdb;
		?>
    <div class="wrap">
        <h2><span><?php _e( 'Stored Links', wplerlm::get_textdomain() ); ?></span></h2>
        <table class="widefat">
            <thead>
            <tr>
                <th scope="col" class="manage-column"><?php _e( 'BlogID', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Blogname', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Link to', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Title', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Properties', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Action', wplerlm::get_textdomain() ); ?></th>
            </tr>
            </thead>
            <tbody>
				<?php
				$res = $wpdb->get_results( "SELECT link_id, UNIX_TIMESTAMP( created ) AS created, blog_id, tourl, rel, target, linktext, linktitle, wpcondition FROM {$wpdb->prefix}wplerlm ORDER BY created DESC" );
				if ( !empty( $res ) && is_array( $res ) ) {
					foreach( $res as $link ) {
						?>
                    <tr class="link-<?php echo absint( $link->link_id ) ?>">
                        <td><?php echo absint( $link->blog_id ) ?></td>
                        <td>
							<?php printf( '<a href="%s" title="%s" target="_blank">%s</a>', get_blog_details( $link->blog_id )->siteurl, get_blog_details( $link->blog_id )->blogname, get_blog_details( $link->blog_id )->blogname ); ?>
                        </td>
                        <td><?php printf( '<a href="%s" target="_blank">%s</a>', $link->tourl, $link->tourl ); ?></td>
                        <td>
							<?php printf( __( 'Link-Text: %s', wplerlm::get_textdomain() ), $link->linktext ); ?><br/>
							<?php printf( __( 'A-Title: %s', wplerlm::get_textdomain() ), $link->linktitle ); ?>
                        </td>
                        <td>
							<?php printf( __( 'Rel: %s', wplerlm::get_textdomain() ), $link->rel ); ?><br />
							<?php printf( __( 'Target: %s', wplerlm::get_textdomain() ), $link->target ); ?><br />
							<?php printf( __( 'Condition: %s', wplerlm::get_textdomain() ), $link->wpcondition ); ?>
                        </td>
                        <td>
                            <a href="<?php echo $_SERVER[ 'PHP_SELF' ] ?>?page=wplerlm-newlink&linkid=<?php echo $link->link_id; ?>" title="<?php _e( 'Edit this link', wplerlm::get_textdomain() ); ?>">
                                <img src="<?php echo WPLER_LM_URL; ?>/pencil.png" alt="<?php _e( 'Edit this link', wplerlm::get_textdomain() ); ?>" />
                            </a>
                            <a href="<?php echo $_SERVER[ 'PHP_SELF' ] ?>?page=wplerlm-newlink&linkid=<?php echo $link->link_id; ?>&delete=1" title="<?php _e( 'Delete this link', wplerlm::get_textdomain() ); ?>">
                                <img src="<?php echo WPLER_LM_URL; ?>delete.png" alt="<?php _e( 'Delete this link', wplerlm::get_textdomain() ); ?>" />
                            </a>
                        </td>
                    </tr>
						<?php
					}
				} else { ?>
				    <tr><td colspan="6"><?php _e('There are no links stored', wplerlm::get_textdomain()); ?></td></tr>
                <?php } ?>
            </tbody>
            <tfoot>
            <tr>
                <th scope="col" class="manage-column"><?php _e( 'BlogID', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Blogname', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Link to', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Title', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Properties', wplerlm::get_textdomain() ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Action', wplerlm::get_textdomain() ); ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
	<?php
	}
	/**
	 * Adds the Page to add a new link to the blog-option
	 *
	 * @access public
	 * @since 1.0
	 * @uses current_user_can, wpdie, absint, esc_url, esc_attr, selected,
	 * 		 get_blog_details, $wpdb, _e,
	 * @return void
	 */
	public function save_link_page() {
		if ( !current_user_can( 'manage_network_options' ) )
			wpdie( __( 'Only the network admin can do anything here.', wplerlm::get_textdomain() ) );
		global $wpdb;
		$siteid = '';
		$target = '_blank';
		if ( !empty( $_POST[ 'save' ] ) ) {
			$into[ 'blog_id' ] = absint( $_POST[ 'blogid' ] );
			$into[ 'tourl' ] = esc_url( $_POST[ 'tourl' ] );
			$into[ 'rel' ] = esc_attr( $_POST[ 'rel' ] );
			$into[ 'target' ] = esc_attr( $_POST[ 'target' ] );
			$into[ 'linktext' ] = esc_attr( $_POST[ 'linktext' ] );
			$into[ 'linktitle' ] = esc_attr( $_POST[ 'linktitle' ] );
			$into[ 'wpcondition' ] = $_POST[ 'condition' ];
			if ( !empty( $_POST[ 'linkid' ] ) && $wpdb->update( $wpdb->base_prefix . 'wplerlm', $into, array( 'link_id' => absint( $_POST[ 'linkid' ] ) ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) ) ) {
				printf( '<div class="updated">%s</div>', __( 'The Link are saved to the table', wplerlm::get_textdomain() ) );
			} elseif ( $wpdb->insert( $wpdb->base_prefix . 'wplerlm', $into, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) ) !== FALSE ) {
				printf( '<div class="updated">%s</div>', __( 'The link are added to the table', wplerlm::get_textdomain() ) );
			}
		} elseif ( !empty( $_GET[ 'linkid' ] ) && !empty( $_GET[ 'delete' ] ) ) {
			$link = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM {$wpdb->base_prefix}wplerlm WHERE link_id = %d", absint( $_GET[ 'linkid' ] ) ), 0 );
			if ( !empty( $link ) && ( $link[ 0 ] == absint( $_GET[ 'linkid' ] ) ) ) {
				$wpdb->query( sprintf( "DELETE FROM {$wpdb->base_prefix}wplerlm WHERE link_id = %d", $link[ 0 ] ) );
				$wpdb->query( "OPTIMIZE TABLE {$wpdb->base_prefix}wplerlm" );
				printf( '<div class="updated">%s</div>', __( 'The Link was deleted', wplerlm::get_textdomain() ) );
			}
		} elseif ( !empty( $_GET[ 'linkid' ] ) ) {
			$link = $wpdb->get_row( $wpdb->prepare( "SELECT link_id, blog_id, tourl, rel, target, linktext, linktitle, wpcondition FROM {$wpdb->base_prefix}wplerlm WHERE link_id = %d", absint( $_GET[ 'linkid' ] ) ) );
			$siteid = !empty( $link->blog_id ) ? absint( $link->blog_id ) : '';
			$target = !empty( $link->target ) ? $link->target : '_blank';
		}
		?>
    <div class="wrap">
        <h2><span><?php _e( 'Add new link', wplerlm::get_textdomain()); ?></span></h2>
        <form action="" method="post">
			<?php if ( !empty( $_GET[ 'linkid' ] ) ): ?>
            <input type="hidden" name="linkid" value="<?php echo $_GET[ 'linkid' ]; ?>" />
			<?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="col"><label for="blogid"><?php _e( 'For wich blog?', wplerlm::get_textdomain() ); ?></label></th>
                    <td><select name="blogid" id="blogid" size="1">
						<?php
						$blogs = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs} WHERE public = '1' && archived = '0' && mature = '0' && spam = '0' && deleted = '0'");
						foreach( $blogs as $blog ) { ?>
                            <option value="<?php echo $blog; ?>" <?php echo selected( $blog, $siteid ); ?>><?php echo get_blog_details( $blog )->blogname . ' (' . get_blog_details( $blog )->siteurl . ')'; ?></option>
							<?php }
						?>
                    </select></td>
                </tr>
                <tr>
                    <th scope="col"><label for="tourl"><?php _e( 'To URL?', wplerlm::get_textdomain() ); ?></label></th>
                    <td><input type="text" name="tourl" id="tourl" class="widefat" value="<?php echo !empty( $link->tourl ) ? $link->tourl : ''; ?>" placeholder="https://" /></td>
                </tr>
                <tr>
                    <th scope="col"><label for="linktext"><?php _e( 'Linktext', wplerlm::get_textdomain() ); ?></label></th>
                    <td><input type="text" name="linktext" id="linktext" value="<?php echo !empty( $link->linktext ) ? $link->linktext : ''; ?>" /></td>
                </tr>
                <tr>
                    <th scope="col"><label for="linktitle"><?php _e( 'Linktitle', wplerlm::get_textdomain() ); ?></label></th>
                    <td><input type="text" name="linktitle" id="linktitle" value="<?php echo !empty( $link->linktitle ) ? $link->linktitle : ''; ?>" /></td>
                </tr>
                <tr>
                    <th scope="col"><label for="rel"><?php _e( 'Rel', wplerlm::get_textdomain() ); ?></label></th>
                    <td><input type="text" name="rel" id="rel" value="<?php echo !empty( $link->rel ) ? $link->rel : ''; ?>" placeholder="nofollow" /></td>
                </tr>
                <tr>
                    <th scope="col"><label for="target"><?php _e( 'Target', wplerlm::get_textdomain() ); ?></label></th>
                    <td><select name="target" id="target" size="1" style="width: 70px;">
                        <option value="_blank" <?php selected( '_blank', $target ); ?>>_blank</option>
                        <option value="_new" <?php selected( '_new', $target ); ?>>_new</option>
                        <option value="_self" <?php selected( '_self', $target ); ?>>_self</option>
                    </select></td>
                </tr>
                <tr>
                    <th scope="col"><label for="condition"><a href="http://codex.wordpress.org/Conditional_Tags" target="_blank"><?php _e( 'Conditional Tag', wplerlm::get_textdomain() ); ?></a></label></th>
                    <td><textarea name="condition" id="condition" rows="3" cols="40"><?php echo !empty( $link->wpcondition ) ? $link->wpcondition : ''; ?></textarea></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="save" value="<?php _e( 'Save', wplerlm::get_textdomain()); ?>" class="button-primary" /></p>
        </form>
    </div>
	<?php
	}
	/**
	 * This methode will print the links for the current BlogID wich are stored
	 * into the linktable
	 *
	 * @access public
	 * @since 1.0
	 * @uses
	 * @return void
	 */
	public function print_link() {
		global $wpdb;
		$blogid = get_current_blog_id();
		$links = $wpdb->get_results( $wpdb->prepare( "SELECT tourl, rel, target, linktext, linktitle, wpcondition FROM {$wpdb->base_prefix}wplerlm WHERE blog_id = %d", absint( $blogid ) ) );
		$show = FALSE;
		if ( !empty( $links ) ) {
			foreach( ( array ) $links as $link ) {
				eval( '$show = (' . $link->wpcondition . ');' );
				if ( !empty( $link->wpcondition ) && TRUE === $show ) {
					$code = '<a href="%1$s" target="%2$s"';
					if ( !empty( $link->rel ) ) $code .= ' rel="%3$s"';
					if ( !empty( $link->linktitle ) ) $code .= ' title="%4$s"';
					$code .= '>%5$s</a>';
					printf(
						$code,
						esc_url( $link->tourl ),
						!empty( $link->target ) ? esc_attr( $link->target ) : '_self',
						!empty( $link->rel ) ? esc_attr( $link->rel ) : 'follow',
						!empty( $link->linktitle ) ? esc_attr( $link->linktitle ) : '',
						!empty( $link->linktext ) ? esc_attr( $link->linktext ) : 'Link'
					);
				}
			}
		}
	}
	/**
	 * On activation this plugin install the tables for the links
	 *
	 * @access public
	 * @since 1.0
	 * @static
	 * @uses $wpdb, dbDelta,
	 * @return void
	 */
	static public function activation_hook() {
		if ( is_multisite() ) {
			global $wpdb;
			$charset = !empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET {$wpdb->charset}" : '';
			$sql  = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}wplerlm (
link_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
blog_id bigint(20) unsigned NOT NULL,
tourl varchar(255) NULL,
rel varchar(25) NULL,
target varchar(25) NULL,
linktext varchar(255) NOT NULL,
linktitle varchar(255) NOT NULL,
wpcondition varchar(255) NULL,
PRIMARY KEY  (link_id)
) {$charset};";
			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		} else {
			printf( '<div class="updated">%s</div>', __( 'This plugin works on multisites only', wplerlm::get_textdomain() ) );
		}
	}
	/**
	 * The hook for the deactivation of the plugin
	 *
	 * @access public
	 * @static
	 * @since 1.0
	 * @uses $wpdb,
	 * @return void
	 */
	static public function deactivation_hook() {
		global $wpdb;
		if ( $wpdb->query( "SHOW TABLES LIKE '{$wpdb->base_prefix}wplerlm'" ) == "1" ) {
			$wpdb->query( "DROP TABLE {$wpdb->base_prefix}wplerlm" );
		}
	}
	/**
	 * Returns the TextDomain of this plugin
	 *
	 * @access public
	 * @static
	 * @since 1.0
	 * @return mixed
	 */
	static public function get_textdomain() {
		return self::$_textDomain;
	}
	/**
	 * Returns the variable of $key into the plugin header
	 *
	 * @param mixed $key
	 * @access public
	 * @since 1.0
	 * @uses get_plugin_data,
	 * @return mixed
	 */
	public function get_header( $key = 'TextDomain' ) {
		if ( !function_exists( 'get_plugin_data' ) )
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		$data = get_plugin_data( __FILE__ );
		return !empty( $data[ $key ] ) ? $data[ $key ] : NULL;
	}
}
