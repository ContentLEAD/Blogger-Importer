<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Demos
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/*
* This sample utilizes the Zend Gdata Client Library, which can be
* downloaded from: http://framework.zend.com/download
*
* This sample is meant to show basic CRUD (Create, Retrieve, Update
* and Delete) functionality of the Blogger data API, and can only
* be run from the command line.
*
* Sample modified to import Brafton News Data
* See readme.txt included for additional dependency info.
*
* To run the sample:
* php Blogger.php --user=email@email.com --pass=password --feedid=apikey --blogid=bloggerid\n");
*/



/**
 * @see Zend_Loader
 */
set_include_path('/usr/share/php/libzend-framework-php');
require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');

/**
 * @see Zend_Gdata_Query
 */
Zend_Loader::loadClass('Zend_Gdata_Query');

/**
 * @see Zend_Gdata_ClientLogin
 */
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');


/**
 * @see Zend_Gdata_Feed
 */
Zend_Loader::loadClass('Zend_Gdata_Feed');


/**
 * Class that contains all simple CRUD operations for Blogger.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Demos
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class SimpleCRUD
{
    /**
     * $blogID - Blog ID used for demo operations
     *
     * @var string
     */
    public $blogID;

    /**
     * $gdClient - Client class used to communicate with the Blogger service
     *
     * @var Zend_Gdata_Client
     */
    public $gdClient;


    /**
     * Constructor for the class. Takes in user credentials and generates the
     * the authenticated client object.
     *
     * @param  string $email    The user's email address.
     * @param  string $password The user's password.
     * @return void
     */
    public function __construct($email, $password)
    {
        $client = Zend_Gdata_ClientLogin::getHttpClient($email, $password, 'blogger');
        $this->gdClient = new Zend_Gdata($client);
    }

    /**
     * This function retrieves all the blogs associated with the authenticated
     * user and prompts the user to choose which to manipulate.
     *
     * Once the index is selected by the user, the corresponding blogID is
     * extracted and stored for easy access.
     *
     * @return void
     */
    public function promptForBlogID()
    {
        $query = new Zend_Gdata_Query('http://www.blogger.com/feeds/default/blogs');
        $feed = $this->gdClient->getFeed($query);
        $this->printFeed($feed);
        $input = getInput("\nSelection");

        //id text is of the form: tag:blogger.com,1999:user-blogID.blogs
        $idText = explode('-', $feed->entries[$input]->id->text);
        $this->blogID = $idText[2];
        print $this->blogID;
    }

    /**
     * This function simply sets the blog ID to a predetermined value for
     * input-independent uploading.
     *
     * @param  string  $ID_   The id of a blog owned by the authenticated user.
     * @return void
     */

    public function setBlogID($ID_){
        $this->blogID = $ID_;
        //print $this->blogID;
    }

    /**
     * This function creates a new Zend_Gdata_Entry representing a blog
     * post, and inserts it into the user's blog. It also checks for
     * whether the post should be added as a draft or as a published
     * post.
     *
     * @param  string  $title   The title of the blog post.
     * @param  string  $content The body of the post.
     * @param  boolean $isDraft Whether the post should be added as a draft or as a published post
     * @return string The newly created post's ID
     */
    public function createPost($title, $content, $summary=null, $isDraft=False, $categories=null, $published=null)
    {
        // We're using the magic factory method to create a Zend_Gdata_Entry.
        // http://framework.zend.com/manual/en/zend.gdata.html#zend.gdata.introduction.magicfactory
        $entry = $this->gdClient->newEntry();

        $entry->title = $this->gdClient->newTitle(trim($title));
        $entry->content = $this->gdClient->newContent(trim($content));
        $entry->content->setType('text');
        $uri = "http://www.blogger.com/feeds/" . $this->blogID . "/posts/default";

        if ($isDraft)
        {
            $control = $this->gdClient->newControl();
            $draft = $this->gdClient->newDraft('yes');
            $control->setDraft($draft);
            $entry->control = $control;
        }

        if($published){
            $entry->published = $this->gdClient->newPublished($published);
        }

        if($summary){
            $entry->summary = $this->gdClient->newSummary($summary);
        }

        //Categories/labels are stored as array regardless of #.
        if($categories){
            $cats = array();
            foreach($categories as $cat){
                $cats[] = $this->gdClient->newCategory($cat['name'], 
                                                        'http://www.blogger.com/atom/ns#');
            }    
            $entry->category = $cats;
        }

        $createdPost = $this->gdClient->insertEntry($entry, $uri);
        //format of id text: tag:blogger.com,1999:blog-blogID.post-postID
        $idText = explode('-', $createdPost->id->text);
        $postID = $idText[2];

        return $postID;
    }

    /**
     * Prints the titles of all the posts in the user's blog.
     *
     * @return void
     */
    public function printAllPosts()
    {
        $query = new Zend_Gdata_Query('http://www.blogger.com/feeds/' . $this->blogID . '/posts/default');
        $feed = $this->gdClient->getFeed($query);
        $this->printFeed($feed);
    }

    /**
     * Retrieves the specified post and updates the title and body. Also sets
     * the post's draft status.
     *
     * @param string  $postID         The ID of the post to update. PostID in <id> field:
     *                                tag:blogger.com,1999:blog-blogID.post-postID
     * @param string  $updatedTitle   The new title of the post.
     * @param string  $updatedContent The new body of the post.
     * @param boolean $isDraft        Whether the post will be published or saved as a draft.
     * @return Zend_Gdata_Entry The updated post.
     */
    public function updatePost($postID, $updatedTitle, $updatedContent, $isDraft)
    {
        $query = new Zend_Gdata_Query('http://www.blogger.com/feeds/' . $this->blogID . '/posts/default/' . $postID);
        $postToUpdate = $this->gdClient->getEntry($query);
        $postToUpdate->title->text = $this->gdClient->newTitle(trim($updatedTitle));
        $postToUpdate->content->text = $this->gdClient->newContent(trim($updatedContent));

        if ($isDraft) {
            $draft = $this->gdClient->newDraft('yes');
        } else {
            $draft = $this->gdClient->newDraft('no');
        }

        $control = $this->gdClient->newControl();
        $control->setDraft($draft);
        $postToUpdate->control = $control;
        $updatedPost = $postToUpdate->save();

        return $updatedPost;
    }

    /**
     * This function uses query parameters to retrieve and print all posts
     * within a specified date range.
     *
     * @param  string $startDate Beginning date, inclusive. Preferred format is a RFC-3339 date,
     *                           though other formats are accepted.
     * @param  string $endDate   End date, exclusive.
     * @return void
     */
    public function printPostsInDateRange($startDate, $endDate)
    {
        $query = new Zend_Gdata_Query('http://www.blogger.com/feeds/' . $this->blogID . '/posts/default');
        $query->setParam('published-min', $startDate);
        $query->setParam('published-max', $endDate);

        $feed = $this->gdClient->getFeed($query);
        $this->printFeed($feed);
    }


    /**
     * This function deletes the specified post.
     *
     * @param  string $postID The ID of the post to delete.
     * @return void
     */
    public function deletePost($postID)
    {
        $uri = 'http://www.blogger.com/feeds/' . $this->blogID . '/posts/default/' . $postID;
        $this->gdClient->delete($uri);
    }

    /**
     * Helper function to print out the titles of all supplied Blogger
     * feeds.
     *
     * @param  Zend_Gdata_Feed The feed to print.
     * @return void
     */
    public function printFeed($feed)
    {
        $i = 0;
        foreach($feed->entries as $entry)

        {
            echo "\t" . $i ." ". $entry->title->text . "\n";
            //print_r($entry);            
            $i++;
        }
    }
} //end class

/**
 * Gets credentials from user.
 *
 * @param  string $text
 * @return string Index of the blog the user has chosen.
 */
function getInput($text)
{
    echo $text.': ';
    return trim(fgets(STDIN));
}


