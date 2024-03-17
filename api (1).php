<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('check_user','UserApiController@testUser');
Route::post('check/user','UserApiController@CheckUser');
Route::get('categories','UserApiController@categories');
Route::post('social/login','UserApiController@social_login');
Route::post('login','UserApiController@login');
Route::post('sendsms','UserApiController@send_sms');
Route::post('reset/otp','UserApiController@reset_otp');
Route::post('check/otp','UserApiController@check_otp');
Route::get('logout/{id}','UserApiController@logout');
Route::get('checkemail','UserApiController@checkemail');


Route::post('register','UserApiController@register');
Route::post('user_register','UserApiController@user_register');
Route::post('update','UserApiController@update_profile');
Route::post('add/post','UserApiController@post_camelClub');
Route::post('add/postclub','UserApiController@post_Club');
Route::post('add/selling','UserApiController@post_camelSelling');
Route::post('add/missing','UserApiController@post_camelMissing');
Route::post('add/treatment','UserApiController@post_camelTreatment');
Route::post('add/food','UserApiController@post_camelFood');
Route::post('add/equipment','UserApiController@post_camelEquipment');
Route::post('add/competition','UserApiController@post_camelCompetition');
Route::post('add/whatsapp/{id}','UserApiController@add_whatsapp');
Route::post('add/chat/{id}','UserApiController@add_chatFlag');
Route::post('add/moving','UserApiController@add_moving');


Route::post('follow','UserApiController@follow');
Route::post('unfollow','UserApiController@unfollow');
Route::post('profile','UserApiController@profile');

Route::post('get/dashboard','UserApiController@dashboard');

Route::get('get/competition','UserApiController@get_competition');
Route::get('get/moving','UserApiController@get_moving');

Route::get('get/competitions/{user_id}','UserApiController@get_competitions');
Route::post('get/camel_club','UserApiController@get_camelClub');
Route::post('get/camel_selling','UserApiController@get_camelSelling');
Route::post('get/camel_missing','UserApiController@get_camelMissing');
Route::post('get/camel_teatment','UserApiController@get_camelTreatment');
Route::post('get/camel_moving','UserApiController@get_camelMoving');
Route::post('get/camel_food','UserApiController@getcamelFood');
Route::post('get/camel_competition','UserApiController@get_camelCompetition');
Route::post('get/camel_equipment','UserApiController@get_camelEquipment');
Route::post('add/camel_moving','UserApiController@add_camelMoving');

//js ko follow kiya wo user ae ge
Route::post('get/following','UserApiController@get_following');

//js ne follow kiya wo user ae ge
Route::post('get/follower','UserApiController@get_follower');


Route::post('get/users','UserApiController@get_users');

Route::post('delete/post','UserApiController@delete_post');

Route::get('view/comment','UserApiController@view_comment');

// ye karna hai
// here comment function is working but due to some problem we use another function get_comment_second
// Route::post('get/comment','UserApiController@get_comment');
Route::post('get/comment','UserApiController@get_comment_second');
Route::post('accept/bid/{id}','UserApiController@accept_bid');
Route::post('get/competition_details','UserApiController@get_competition_details');

Route::post('add/comment','UserApiController@add_comment');
Route::post('add/competition/comment','UserApiController@add_competition_comment');
Route::post('add/reply','UserApiController@add_reply');
Route::post('add/like','UserApiController@post_likes');
Route::post('add/competition/like','UserApiController@competition_likes');
Route::post('add/bid','UserApiController@add_bid');

Route::post('comment/like','UserApiController@comment_like');

Route::post('get/bids','UserApiController@get_bids');

Route::post('add/sale','UserApiController@sell');
Route::post('get/sales','UserApiController@get_sales');
Route::post('get/purchases','UserApiController@get_purchases');

Route::post('update/image','UserApiController@update_image');

Route::post('verify/phone','UserApiController@verify_phone');
Route::post('password/reset','UserApiController@passwod_reset');
Route::post('change/password','UserApiController@change_password');
Route::post('add/notification','UserApiController@add_notification');
Route::post('get/notification','UserApiController@get_notification');
Route::post('delete/notification','UserApiController@delete_notification');
Route::post('delete/all/notification','UserApiController@delete_all_notification');
Route::get('notification/{id}','UserApiController@notification');

Route::post('get/news_comments_like','UserApiController@news_comments_likes_by_user_id');
Route::post('get/news','UserApiController@get_news');
Route::get('get/news_comment','UserApiController@get_news_comments');
Route::post('add/news/comment','UserApiController@add_newscomment');
Route::post('add/news/like','UserApiController@add_newslike');
Route::get('get/news-like','UserApiController@get_news_like');
Route::post('view/post','UserApiController@view_post');
Route::get('post/view','UserApiController@post_view');
Route::post('single/post','UserApiController@single_post');
Route::post('add/share','UserApiController@share_count');
Route::get('getshare','UserApiController@get_share');
Route::post('add/rating','UserApiController@add_rating');
Route::post('userprofile','UserApiController@user_profile');
Route::post('get/rating','UserApiController@get_rating');

Route::post('add/view','UserApiController@add_view');
Route::post('add/sharess','UserApiController@add_share');
Route::post('get/followers','UserApiController@get_followers');
Route::get('get/survey/{id}','UserApiController@get_survey');
Route::get('survey','UserApiController@survey');
Route::post('get/survey','UserApiController@get_surveys');
Route::post('check/survey/by/user_id','UserApiController@check_survey_by_user_id');
Route::post('add/survey','UserApiController@add_survey');
Route::post('get/camelmove','UserApiController@getcamelMove');

Route::post('add/camel_female','UserApiController@add_female_camel');
Route::post('get/camel_female','UserApiController@get_camel_female');
Route::post('add/marketing','UserApiController@add_marketing');
Route::post('get/marketing','UserApiController@get_marketing');
Route::get('getmsg/{id}', 'UserApiController@get_msg');
Route::get('getgroupchat/{id}', 'UserApiController@get_group_chat');
Route::get('getmsgchat/{id}/{id1}', 'UserApiController@get_msgchat');
Route::post('sendmsg', 'UserApiController@send_msg');

// added by eneyat
Route::get('markedasread/{sender_id}/{reciever_id}', 'UserApiController@markedAsRead');
Route::post('message/delete', 'UserApiController@deleteMessage');
Route::post('manage/friendrequest', 'UserApiController@friendRequest');
Route::get('friendlist/{id}', 'UserApiController@friendList');
Route::get('getfriendrequest/{id}', 'UserApiController@getFriendRequest');
Route::get('friendshipstatus/{user_id}/{friend_id}', 'UserApiController@CheckFriendShipStatus');
Route::post('creategroup', 'UserApiController@createGroup');
Route::get('getgroups/{user_id}', 'UserApiController@getGroups');
Route::post('groupconversation', 'UserApiController@groupConversation');
Route::get('getgroupconversation/{id}', 'UserApiController@getGroupConversation');

Route::get('getgrouplist/{id}', 'UserApiController@getGroupList');
/*******************************/
Route::get('getmsgchat/{id}/{id1}', 'UserApiController@get_msgchat');
Route::get('getCheckSurveyByUserId/{uid}/{id}', 'UserApiController@getSurveyList');
Route::get('get/userDropdown/{id}','UserApiController@getDropdownUsers');
Route::get('get/fetchUser/{user_id}','UserApiController@fetch_user');
Route::get('getAbout','UserApiController@getAbout');
Route::get('getSponsars','UserApiController@getSponsars');
Route::get('getprivacies','UserApiController@getprivacies');
Route::post('checkBid','UserApiController@checkBid');
Route::get('withdrawBids/{id}','UserApiController@withdrawBids');
Route::get('getBank', 'UserApiController@getBank');
Route::post('competition_winner', 'UserApiController@competition_winner');
Route::get('getPostByCategories/{id}', 'UserApiController@getPostByCategories');   
Route::get('getBidsByPostId/{id}', 'UserApiController@getBidsByPostId'); 
Route::get('/aboutus', 'UserApiController@get_about');
Route::get('/privacy', 'UserApiController@get_privacy');
Route::post('/postclub', 'UserApiController@get_postclub');
Route::post('/categoryFilter/by/user_id', 'UserApiController@category_filter_by_user_id');
Route::post('/award_bids', 'UserApiController@award_bids');
Route::post('/add-advertisement', 'UserApiController@add_advertisement');
Route::get('/get-advertisement', 'UserApiController@get_advertisement');
Route::post('/news-comment-like', 'UserApiController@news_comment_like');
Route::get('get/fetchUser/{id}','UserApiController@fetch_user');
Route::get('get/bids/{user_id}','UserApiController@get_bids_by_user_id_post_id');
Route::get('getLastBidPrice/{id}', 'UserApiController@getLastBidPrice');

Route::post('getMultipleUsersDetails', 'UserApiController@getMultipleUsersDetails');
Route::post('add/block', 'UserApiController@add_block');
Route::post('chat/request/notification','UserApiController@chat_request_notification');
Route::post('closed/bid','UserApiController@bid_closed');
Route::get('test','UserApiController@test');
Route::get('getAllFollowers','UserApiController@getAllFollowers');
Route::get('getAllFollowers/{id}','UserApiController@getAllFollowers');
Route::get('getfollowing/{id}','UserApiController@getAllfollowing');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
