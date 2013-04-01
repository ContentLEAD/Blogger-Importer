<?php
define( "ABSPATH", dirname(__FILE__) . "/");

require_once( ABSPATH . 'Blogger.php');
require_once( ABSPATH . 'c_settings.php');
require_once( ABSPATH . 'SampleAPIClientLibrary/ApiHandler.php');

class BraftonBlogger extends SimpleCRUD {
    private $db;
    private $api_key;

    public function __construct($user, $pass, $blog_id, $cxn, $key){
        parent::__construct($user, $pass);
        $this->db = $cxn;
        $this->api_key = $key;
        $this->setBlogID($blog_id);
    }    


    /**
     * Invoke constructor with settings defined in local .txt files
     *
     * Input is handled by
     *      database: db_settings.txt
     *      Blogger: blogger_settings.txt
     *      Brafton API: brafton_key.txt
     */
    public static function factory(){
        $cxn = db_connect_from_file();
        $blogger_creds = blogger_creds_from_file();
        $key = api_key_from_file();
        return new BraftonBlogger($blogger_creds['user'], 
                                  $blogger_creds['pass'],
                                  $blogger_creds['blogid'],
                                  $cxn,
                                  $key);          

    }


    /**
     * This function searches for a Brafton article ID in the local database 
     * and returns the Blogger post ID if found.
     *
     * @param  articleID Brafton article ID to search for.
     * @return post ID or null if not found
     */
    public function post_exists($articleID){
        $query = sprintf("SELECT * from bloggerIDs 
                          WHERE braftonID = %d", 
			 $articleID);
        $result = mysql_query($query, $this->db);
        
        switch (mysql_num_rows($result)){
            case 0 : 
                return null;
            case 1: 
                $row = mysql_fetch_assoc($result);
                return $row['bloggerID'];
            default:
                echo "Too many row matches!!\n";
                return null;        
        }
    }


    /**
     * Runs the sample.
     *
     * @return void
     */
    public function retrieveCurrentArticles() {
        $url = 'http://api.brafton.com/';
        $article_api = new ApiHandler($this->api_key, $url);
        $article_list = $article_api->getNewsHTML();
        
        //loop through articles
        foreach($article_list as $article){
            $draft = False;
            $publish_time = $article->getPublishDate();
            $content = $article->getText();
            $photos = $article->getPhotos();
            if(!empty($photos)){
                $lg = $photos[0]->getLarge();
                if(!empty($lg)){
                    $content = '<img src="' . $lg->getURL() . 
                               '" class="braftonpic"> ' .  $content;
                }
            }
            $cat_array = $this->categoryList($article->getCategories());
            
            if($postID = $this->post_exists($article->getId())){
	            echo "Updating the previous post and publishing it.\n";
                $updatedPost = $this->updatePost($postID, 
                                                 $article->getHeadline(), 
                                                 $content, False);
               
	        } else{
              $postID = $this->createPost($article->getHeadline(), 
                                          $content,    
                                          $article->getExtract(),
                                          $draft,
                                          $cat_array,
                                          $publish_time);
              $sql_title = mysql_real_escape_string($article->getHeadline(), 
                                                    $this->db);
              $query = "INSERT INTO bloggerIDs 
                        VALUES (".$article->getId().", '$sql_title', $postID)";
              echo $query;                
              mysql_query($query, $this->db) 
                          or die("Invalid query: " . mysql_error());
           }

        }       
        echo "Printing all posts.\n";
        $this->printAllPosts();
    }

    /**
     * Helper function to create properly formatted array from 
     * categories object
     */
    private function categoryList($category_obj){
        $cat_out = array();
        foreach($category_obj as $c){
            $cat['id'] = $c->getID();
            $cat['name'] = $c->getName();
            $cat_out[] = $cat;
        }
        
       return $cat_out; 
    }


    public static function run(){
        $bb = BraftonBlogger::factory();
        $bb->retrieveCurrentArticles();
    }
}
 

/**
 * Return the API key stored in the appropriate settings file
 */
function api_key_from_file(){
    $api_key = file_to_array( ABSPATH . 'brafton_key.txt');

    return $api_key['key'];
}    

/**
 * Return the blogger credentials stored in the appropriate settings file
 */
function blogger_creds_from_file(){
    $blogger_creds = file_to_array( ABSPATH . 'blogger_settings.txt');

    return $blogger_creds;
}

BraftonBlogger::run();

?>
