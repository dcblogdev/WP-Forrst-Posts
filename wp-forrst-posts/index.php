<?php
/*
Plugin Name: WP Forrst Posts
Plugin URI: http://daveismyname.com/freebies/wordpress-forrst-plugin-for-pages/
Description: This plugin allows you to display your 25 latest Forrst (public) posts using the Forrst API.
Version: 1.0
Author: David Carr
Author URI: http://www.daveismyname.com
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


/* 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_Forrst_Posts {
    
    //define constants for the plugin
    const plugin_name = 'WP Forrst Posts';
    const plugin_slug = 'wp_forrst_posts';
    const class_slug = 'WP_Forrst_Posts';
    const register_slug = 'wp_forrst_posts_options';

    public $options;

    public function __construct(){
        $this->register_settings_and_fields();
        $this->options = get_option('wp_forrst_posts');
    }

    public function add_menu_page(){
        add_options_page(self::plugin_name, self::plugin_name, 'administrator', __FILE__, array('wp_forrst_posts', 'display_options_page'));
    }

    public function display_options_page(){
    ?>

        <div class='wrap'>

            <?php screen_icon();?>
            <h2><?php echo self::plugin_name;?></h2>
            <p>To display your Forrst posts enter [wp_forrst_posts] on your desired page.</p>

            <form action="options.php" method="post" enctype="multipart/form-data">
            <?php settings_fields(self::plugin_slug);?>
            <?php do_settings_sections(__FILE__);?>
            
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes');?>"/>
            </p>
            </form>


        </div>
    <?php
    }

    public function register_settings_and_fields(){
        register_setting(self::plugin_slug, self::plugin_slug); //third param option call back function
        add_settings_section('main_section', 'Main Settings', array($this,'main_section_cb') ,__FILE__); //id, title, cb, page
        add_settings_field('username','Your Forrst Username', array($this,'username_setting'), __FILE__, 'main_section');
    }

    public function main_section_cb(){
        //optional callback

    }

    public function username_setting(){
        echo '<input name="wp_forrst_posts[username]" type="text" value="'.$this->options[username].'" />';
    }

}

add_action('admin_menu', 'WP_Forrst_Options');
add_action('admin_init', 'WP_Forrst_Init');

function WP_Forrst_Options(){
    WP_Forrst_Posts::add_menu_page();
}

function WP_Forrst_Init(){
    new WP_Forrst_Posts();
}

//add shortcode to its hook.
add_shortcode('wp_forrst_posts','WP_Forrst_Load_Posts');

//function to get the file contents from the forrstdata.php file using curl
function curl_file_get_contents($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

//function to load all posts call by a shortcode of [forrst_posts]
function WP_Forrst_Load_Posts(){

    require('caching.php');

    $options = get_option('wp_forrst_posts');
    $username = $options['username'];

    //initalise object pass location of file to store the data and the resource to call, forrst with the username specified in the plugin options
    //make sure username is not empty.
    if($username !=''){
        //cache data if not already stored
        $caching = new Caching(WP_PLUGIN_DIR.'/wp-forrst-posts/forrstdata.php','https://forrst.com/api/v2/users/posts?username='.$username);
    
        //read data
        $fromfile = curl_file_get_contents(WP_PLUGIN_URL.'/wp-forrst-posts/forrstdata.php'); 

        //unserialise data
        $data = unserialize(base64_decode($fromfile));

        //if there is data to work with
        if(!empty($data))  
        {    
            
                //count number of items in array
                $data_count = count($data['resp']) -1;  
              
                //loop through array items
                for($i = 0; $i <= $data_count; $i++){            
                ?>

                    <!-- Begin Post -->
                    <div class="post">
                        <h1 class="title"><a href="<?php echo $data['resp'][$i]['post_url'];?>"><?php echo $data['resp'][$i]['title'];?></a></h1>

                        <?php 
                        if($data['resp'][$i]['post_type'] == 'snap'){                    
                            echo '<p style="text-align:center;"><a href="'.$data['resp'][$i]['post_url'].'"><img style="display:inline;" src="'.$data['resp'][$i]['snaps']['mega_url'].'" class="attachment" alt="" /></a>';
                        }
                        ?>        
                        
                        
                        <!-- Begin Meta Info -->
                        <div class="meta">
                            <div class="meta-info">
                                <p>
                                    Date: <?php echo date('jS \of F Y', strtotime($data['resp'][$i]['created_at']));?>
                                    | Comments: <?php echo $data['resp'][$i]['comment_count'];?> Comments
                                    | Categories:
                                        <i>
                                        <?php
                                            $tag_array = explode(',', $data['resp'][$i]['tag_string']);

                                            foreach ($tag_array as $tag) {
                                                echo '<a href="http://forrst.com/posts/tagged?with=' . $tag . '">' . $tag . '</a>, ';
                                            }
                                        ?>
                                        </i>                                    
                                </p>       
                            </div>
                            <div class="line"></div>
                        </div>
                        <!-- End Meta Info -->
                        
                        <?php echo $data['resp'][$i]['formatted_description'];?>
                         <a href="<?php echo $data['resp'][$i]['post_url'];?>" class="more">Continue Reading and comment on Forrst →</a>
                    </div>
                    <!-- End Post -->
             
                <?php 
                } 
          
        } else {
            echo '<p>Could not load data!</p>';
        }
    }

}


?>
