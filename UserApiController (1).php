<?php

namespace App\Http\Controllers;
use App\Services\MsegatService;
use App\Advertisment;
use App\awardBids;
use App\Category;
use App\Comment;
use App\CommentLike;
use App\CommentReply;
use App\PostThumbnail;
use App\Competition;
use App\CompetitionWinner;
use App\Friend;
use App\Message;
use App\Moving;
use App\News;
use App\CompetitionParticipant;
use App\NewsComment;
use App\NewsCommentLike;
use App\NewsLike;
use App\CompetitionLike;
use App\CompetitionComment;
use App\CompetitionCommentLike;
use App\NewsRating;
use App\Notification;
use App\Post;
use App\PostBid;
use App\PostImage;
use App\PostLike;
use App\PostVideo;
use App\Sale;
use App\Settings;
use App\Share;
use App\Survey;
use App\ViewPostHistory;
use App\SurveySubmit;
use App\surveyDetail;
use App\User;
use App\Block;
use App\UserFollower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use mysql_xdevapi\Exception;
use stdClass;

class UserApiController extends Controller
{

    private $firebase_key;
    public function __construct()
    {
        // Set the value for the global variable in the constructor
        $this->firebase_key = '';
    }


    public function social_login_old(Request $request)
    {
        $request->validate([
            "firebase" => "required",
            "device_type" => "required",
            "token" => "required",
        ]);
        $user = User::where('firebaseID', $request->firebase)->where('role', 2)->first();
        if ($user) {
            if ($user->status == "active") {
                $user->device_type = $request->device_type;
                $user->token = $request->token;
                $user->save();
                return response()->json(['status' => true, 'user' => $user]);
            } else {
                return response()->json(['status' => false, 'message' => "Your account status is InActive"]);
            }

        } else {
            return response()->json(['status' => false, 'message' => "Email or Password is incorrect"]);
        }

    }
    
    public function social_login(Request $req)
    {
        $req->validate([
            "socialType" => "required",
            "socialToken" => "required",
            "device_token"=> "required",
            'social_id'=> 'required'
        ]);
        

        
        $social_check = User::where('social_id', $req->social_id)->first();
        
     
        if($social_check->status=='inActive'){
             return response()->json([
                        'status' => false,
                        'error' => 'غير مسموح لك بتسجيل الدخول',
                    ]);
        }else {$current = User::find($social_check->id);
                if ($current) {
                    $followers = UserFollower::where('follower_id', $social_check->id)->get();
                    $follower_count = $followers->count();

                    $following = UserFollower::where('user_id', $social_check->id)->get();
                    $following_count = $following->count();

                    $likes = PostLike::where('user_id', $social_check->id)->get();
                    $likes_count = $likes->count();

                    $bids_count = 0;

                    $posts = Post::where('category_id', 2)->where('user_id', $social_check->id)->get();
                    foreach ($posts as $post) {
                        $bids_count = $bids_count + $post->bids->count();
                    }

                    $shares = $social_check->shares;

                    $following_user = UserFollower::where('follower_id', $social_check->id)->where('user_id', $current->id)->first();
                    if ($following_user) {
                        $following_user = true;
                    } else {
                        $following_user = false;
                    }

                    $sales = Sale::where('seller_id', $social_check->id)->orWhere('purchaser_id', $social_check->id)->get();
                    $sales_count = $sales->count();

                    $posts = Post::where('user_id', $social_check->id)->withCount('likes')->withCount('comments')->get();
                    $return_arr = array();

                    if ($posts->count() > 0) {
                        foreach ($posts as $post) {
                            $category = $post->category;
                            $user = $post->user;

                            $post->image = explode(",", $post->image);

                           
                            $liked = false;

                            $check = PostLike::where('post_id', $post->id)->where('user_id', $social_check->id)->first();
                            if ($check) {
                                $liked = true;

                            }

                            $return_arr[] = array('post' => $post, 'liked' => $liked);

                        }
                    }

                    return response()->json([
                        'status' => true,
                        'user' => $social_check,
                        'follow_status' => $following_user,
                        'follwers' => $follower_count,
                        'following' => $following_count,
                        'offers' => $bids_count,
                        'shares' => $shares,
                        'sales_purchase' => $sales_count,
                        'likes' => $likes_count,
                        'posts' => $return_arr,
                    ]);
                }
        }

    }
    public function login_old(Request $req)
    {
        $user = user::where('phone', $req->phone)->first();
        if (!$user || !hash::check($req->password, $user->password)) {
            return ["error" => "Phone and Password is not matched"];
        }

        return $user;
    }

    public function login(Request $req)
    {
        $req->validate([
            
            'device_type' => 'required',
            'device_token' => 'required'
            
            ]);
            
        $user = user::where('phone', $req->phone)->first();
        if($user->status=='inActive'){
             return response()->json([
                        'status' => false,
                        'error' => 'غير مسموح لك بتسجيل الدخول',
                    ]);
        }
        
        if (!$user || !hash::check($req->password, $user->password)) {
            return ["error" => "Phone and Password is not matched"];
        } else {

            if ($user) {
                
                User::where(['id'=> $user->id])->update(['device_type'=> $req->device_type, 'device_token'=> $req->device_token]);
                $current = User::find($user->id);
                if ($current) {
                    $followers = UserFollower::where('follower_id', $user->id)->get();
                    $follower_count = $followers->count();

                    $following = UserFollower::where('user_id', $user->id)->get();
                    $following_count = $following->count();

                    $likes = PostLike::where('user_id', $user->id)->get();
                    $likes_count = $likes->count();

                    $bids_count = 0;

                    $posts = Post::where('category_id', 2)->where('user_id', $user->id)->get();
                    foreach ($posts as $post) {
                        $bids_count = $bids_count + $post->bids->count();
                    }

                    $shares = $user->shares;

                    $following_user = UserFollower::where('follower_id', $user->id)->where('user_id', $current->id)->first();
                    if ($following_user) {
                        $following_user = true;
                    } else {
                        $following_user = false;
                    }

                    $sales = Sale::where('seller_id', $user->id)->orWhere('purchaser_id', $user->id)->get();
                    $sales_count = $sales->count();

                    $posts = Post::where('user_id', $user->id)->withCount('likes')->withCount('comments')->get();
                    $return_arr = array();

                    if ($posts->count() > 0) {
                        foreach ($posts as $post) {
                            $category = $post->category;
                            $user = $post->user;

                            $post->image = explode(",", $post->image);

                           
                            $liked = false;

                            $check = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();
                            if ($check) {
                                $liked = true;

                            }

                            $return_arr[] = array('post' => $post, 'liked' => $liked);

                        }
                    }

                    return response()->json([
                        'status' => true,
                        'user' => $user,
                        'follow_status' => $following_user,
                        'follwers' => $follower_count,
                        'following' => $following_count,
                        'offers' => $bids_count,
                        'shares' => $shares,
                        'sales_purchase' => $sales_count,
                        'likes' => $likes_count,
                        'posts' => $return_arr,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'غير قادر على العثور على المستخدم الحالي',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'غير قادر على العثور على المستخدم',
                ]);
            }
        }

        return $user;
    }

    public function checkemail(Request $req)
    {
        $user = user::where('phone', $req->phone)->first();

        if ($user) {
            return response()->json([
                'status' => false,
                'message' => 'الهاتف موجود بالفعل',
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'مستخدم جديد',
            ]);
        }
        
    }
    public function logout($id)
    {  
        $user = User::where('id', $id)->update(['device_token'=> '', 'socialToken'=>'']);
        if ($user == 1) {
            
            return response()->json(['message' => 'تم تسجيل الخروج بنجاح'], 200);
        } else {
            return response()->json(['message' => 'غير قادر على تسجيل الخروج']);
            }

        
    }

    //  public function login(Request $req)
    // {
    //     $user= user::where('email',$req->email)->first();
    //     if(!$user || !hash::check($req->password,$user->password))
    //     {
    //         return ["error"=>"Email and Password is not matched"];
    //     }

    //     return $user;
    // }

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         "phone" => "required",
    //         "password" => "required",
    //         "device_type" => "required",
    //         "token" => "required"
    //     ]);

    //     $user = User::where('role',2)->where('phone', $request->phone)->first();
    //     if ($user) {
    //         if ($user->status == "active") {
    //             if (Hash::check($request->password, $user->password)) {

    //                 $user->device_type = $request->device_type;
    //                 $user->token = $request->token;
    //                 $user->save();

    //                 return response()->json(['status' => true, 'user' => $user]);
    //             } else {
    //                 return response()->json(['status' => false, 'message' => "Phone number or Password is incorrect"]);
    //             }
    //         } else {
    //             return response()->json(['status' => false, 'message' => "Your account status is InActive"]);
    //         }

    //     } else {
    //         return response()->json(['status' => false, 'message' => "Phone number or Password is incorrect"]);
    //     }
    // }

    public function testUser(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'phone' => 'required',
        ]);
        $users = User::where('email', $request->email)->get();
        $count = $users->count();
        if ($count > 0) {
            return response()->json(['status' => false, 'message' => 'Email already exist']);
        } else {

            $users = User::where('phone', $request->phone)->get();
            $count = $users->count();

            if ($count > 0) {
                return response()->json(['status' => false, 'message' => 'Phone Number already exist']);
            } else {
                return response()->json(['status' => true, 'message' => 'Both not exist']);
            }
        }
    }

    public function CheckUser(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'phone' => 'required',
        ]);
        $users = User::where('email', $request->email)->get();
        $count = $users->count();
        if ($count > 0) {
            return response()->json(['status' => false, 'message' => 'Email already exist']);
        } else {

            $users = User::where('phone', $request->phone)->get();
            $count = $users->count();

            if ($count > 0) {
                return response()->json(['status' => false, 'message' => 'Phone Number already exist']);
            } else {
                return response()->json(['status' => true, 'message' => 'Both not exist']);
            }
        }
    }

    public function user_register(Request $req)
    {

        $user = Validator::make($req->all(), [
            "phone" => "required",
        ]);
        $user = User::where('phone', $req->phone)->first();
        if ($user) {
            return response()->json(['status' => false, 'error' => 'phone is already exist']);
        } else {
            return response()->json(['status' => true, 'success' => 'phone doesnot exist']);
        }

    }

    public function register(Request $req)
    {
        $user = Validator::make($req->all(), [
            "name" => "required",
            "phone" => "required",
            "password" => "required",
            "device_type" => 'required',
            "device_token" => 'required'
        ]);
        if ($user->fails()) {
            return response()->json(['status' => false, 'data' => $user->errors()]);
        } 
       
        
              $image_64 = "data:image/png;base64,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";
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extension;
            Storage::disk('public')->put('/images/profiles/' . $imageName, base64_decode($image));
            $user = user::where('phone', $req->phone)->first();
            $user->name = $req->name;
            $user->phone = $req->phone;
            $user->password = Hash::make($req->password);
            $user->email = $req->email;
            $user->image = $imageName;
            $user->location = $req->location;
            $user->device_type = $req->device_type;
            $user->device_token = $req->device_token;
            $user->firebaseID = $req->firebase_id;
            $user->whatsapp_status = 'False';
            $user->update();
            $bids_count=0;
            
            $following_user = UserFollower::where('follower_id', $user->id)->where('user_id', $user->id)->first();
            if($following_user){
                $following_user=$following_user;
            }else{
                $following_user=[];
            }
            $followers = UserFollower::where('follower_id', $user->id)->get();
            if($followers){
                $followers=$followers;
            }else{
                $followers=[];
            }
            $follower_count = $followers->count();
            if($follower_count>0){
                $follower_count=$follower_count;
            }else{
                $follower_count=0;
            }
            $following = UserFollower::where('user_id', $user->id)->get() ??[];
            $following_count = $following->count() ?? 0;
            $shares = $user->shares;
            $posts = Post::where('category_id', 2)->where('user_id', $user->id)->get() ?? [];
            if(count($posts)>0){
               foreach ($posts as $post) {
                                    $bids_count = $bids_count + $post->bids->count();
                                } 
            }else{
                $bids_count=0;
                
            }
                                
            $sales = Sale::where('seller_id', $user->id)->orWhere('purchaser_id', $user->id)->get() ?? [];
            $sales_count = $sales->count() ?? 0;
            $likes = PostLike::where('user_id', $user->id)->get() ?? [];
            $likes_count = $likes->count() ?? 0;
          
            $posts = Post::where('user_id', $user->id)->withCount('likes')->withCount('comments')->get() ?? [];
            $return_arr = array();
            if ($posts->count() > 0) {
                                    foreach ($posts as $post) {
                                        $category = $post->category;
                                       
                                        $user = $post->user;
                                        $post->image = explode(",", $post->image);
                                        $liked = false;
                                        $check = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();
                                        if ($check) {
                                            $liked = true;

                                        }
                                    }
           
            }else{
                $liked = false;
                $post=[];
            }   
           $return_arr[] = array('post' => $post, 'liked' => $liked);
            return response()->json([
                                    'status' => true,
                                    'user' => $user,
                                    'follow_status' => $following_user,
                                    'follwers' => $follower_count,
                                    'following' => $following_count,
                                    'offers' => $bids_count,
                                    'shares' => $shares,
                                    'sales_purchase' => $sales_count,
                                    'likes' => $likes_count,
                                    'posts' => $return_arr,
                                ]);
             
            
            
            
            
            
            
            
           
    }

    // public function register(Request $request){

    //     $request->validate([
    //         "phone"=>"required",
    //         "name"=>"required",
    //         "device_type" => "required",
    //         "token" => "required"

    //     ]);

    //     $users = User::where('phone', $request->phone)->get();
    //     $count = $users->count();
    //     if ($count > 0) {
    //         return response()->json(['status' => false, 'message' => 'Phone number already exist']);
    //     } else {

    //         if($request->firebase != ""){

    //             $random=rand(100,10000);

    //             $password = $request->name.$random;
    //             $c_password = $request->name.$random;

    //         }else{
    //             $password = $request->password;
    //             $c_password = $request->confirm_password;

    //         }

    //         if($password == $c_password){

    //             $url = "https://img.icons8.com/bubbles/2x/user";
    //             $contents = file_get_contents($url);
    //             $image = substr($url, strrpos($url, '/') + 1) . time() . '.png';
    //             Storage::put('images/profiles/' . $image, $contents);

    //             $user = new User();
    //             if ($request->firebase != "") {
    //                 $user->firebaseID = $request->firebase;

    //             }else{
    //                 $user->firebaseID = "";
    //             }

    //             $user->name = $request->name;
    //             // $user->email = $request->email;

    //             $user->phone = $request->phone;
    //             $user->password = bcrypt($request->password);

    //             $user->image = $image;
    //             $user->device_type = $request->device_type;
    //             $user->token = $request->token;
    //             $user->status = "active";
    //             $user->token = "";
    //             $user->role = 2;
    //             if ($user->save()) {

    //                 return response()->json(['status' => true, 'user' => $user]);
    //             } else {
    //                 return response()->json(['status' => false, 'message' => 'Error Occurred']);
    //             }
    //         }else{
    //             return response()->json(['status' => false, 'message' => 'Password does not match']);
    //         }
    //     }
    // }

//     public function update_profile(Request $request){

//         $user = User::find($request->user_id);
    //         if($user) {
    //             try {
    //                 $users = User::where('phone', $request->phone)->where('id', '!=', $user->id)->get();
    //                 $count = $users->count();
    //                 if ($count > 0) {
    //                     return response()->json(['status' => false, 'message' => 'Phone Number already exist']);
    //                 } else {
    //                     if ($request->has('image') && $request->image != "") {
    //                         $image = $request->file('image');
    // //                        $image = $request->image;
    //                         $imageName = "profile" . md5(rand(100, 1000)) . rand(100, 1000) . ".jpeg";
    //                         Storage::disk('public')->put('/images/profiles/' . $imageName, base64_decode($image));

//                         if (Storage::disk('public')->exists('/images/profiles/' . $imageName)) {
    //                             $user->image = $imageName;
    //                         }
    //                     }

//                     if ($request->has('password') && $request->password != "") {
    //                         $user->password = bcrypt($request->password);
    //                     }

//                     $user->name = $request->name;
    //                     $user->phone = $request->phone;

//                     if ($user->save()) {

//                         return response()->json(['status' => true, 'user' => $user]);
    //                     } else {
    //                         return response()->json(['status' => false, 'message' => 'Error Occurred']);
    //                     }
    //                 }
    //             } catch (Exception $e) {
    //                 return response()->json(['status' => false, 'message' => $e->getMessage()]);
    //             }
    //         } else {
    //             return response()->json(['status' => false, 'message' => 'Invalid user id']);
    //         }

//     }

    public function update_profile(Request $request)
    {
        //  return response()->json($request->all());
        if($request->name)
        {
            $post= Post::where('user_id',$request->user_id)->update(['user_name'=> $request->name]);
        }
         if($request->phone)
        {
            $post= Post::where('user_id',$request->user_id)->update(['user_phone'=> $request->phone]);
        }
        
     
        
        if($request->user_id){
        $user = User::find($request->user_id);
        $user->is_complete= 1; 
        
        }

        
        if ($user) {
            try {
                // $users = User::where('phone', $request->phone)->where('id', '!=', $user->id)->get();
                // $count = $users->count();
                // if ($count > 0) {
                //     return response()->json(['status' => false, 'message' => 'البريد الالكتروني موجود بالفعل']);
                // } else {
                
                    if ($request->has('image') && $request->image != "") {
                        $image_64 = $request->image; //your base64 encoded data

                        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

                        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                        $image = str_replace($replace, '', $image_64);

                        $image = str_replace(' ', '+', $image);

                        $imageName = Str::random(10) . '.' . $extension;

                        Storage::disk('public')->put('/images/profiles/' . $imageName, base64_decode($image));
                        $user->image = $imageName;
                        // if (Storage::disk('public')->exists('/images/profiles/' . $imageName)) {

                        // }
                    }

                    if ($request->has('password') && $request->password != "") {
                        
                        $user->password = bcrypt($request->password);
                    }
                    
                    if ($request->has('email')) {
                        
                        $user->email = $request->email;
                    
                    }


                    if ($request->has('name') && $request->name != "") {
                        $user->name = $request->name;
                    }
                    if ($request->has('phone')  && $request->phone != "") {
                        
                        $user->phone = $request->phone;
                        
                    }

                    if ($request->has('location') && $request->location != "") {
                        $user->location = $request->location;
                    }
                    if ($request->has('phone_status') && $request->phone_status != "") {
                        $user->phone_status = $request->phone_status;
                    }

                    if ($user->update()) {

                        if ($user) {
                            $current = User::find($user->id);
                            if ($current) {
                                $followers = UserFollower::where('follower_id', $user->id)->get();
                                $follower_count = $followers->count();

                                $following = UserFollower::where('user_id', $user->id)->get();
                                $following_count = $following->count();

                                $likes = PostLike::where('user_id', $user->id)->get();
                                $likes_count = $likes->count();

                                $bids_count = 0;

                                $posts = Post::where('category_id', 2)->where('user_id', $user->id)->get();
                                foreach ($posts as $post) {
                                    $bids_count = $bids_count + $post->bids->count();
                                }

                                $shares = $user->shares;

                                $following_user = UserFollower::where('follower_id', $user->id)->where('user_id', $current->id)->first();
                                if ($following_user) {
                                    $following_user = true;
                                } else {
                                    $following_user = false;
                                }

                                $sales = Sale::where('seller_id', $user->id)->orWhere('purchaser_id', $user->id)->get();
                                $sales_count = $sales->count();

                                $posts = Post::where('user_id', $user->id)->withCount('likes')->withCount('comments')->get();
                                $return_arr = array();
                                if ($posts->count() > 0) {
                                    foreach ($posts as $post) {
                                        $category = $post->category;
                                        $user = $post->user;
                                        // $arr = explode(",", $post->images);

                                        $post->image = explode(",", $post->image);
                                        //$images = $post->$arr;
                                        // $images = $post->images;

                                        $liked = false;

                                        $check = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();
                                        if ($check) {
                                            $liked = true;

                                        }

                                        $return_arr[] = array('post' => $post, 'liked' => $liked);

                                    }
                                }

                                return response()->json([
                                    'status' => true,
                                    'user' => $user,
                                    'follow_status' => $following_user,
                                    'follwers' => $follower_count,
                                    'following' => $following_count,
                                    'offers' => $bids_count,
                                    'shares' => $shares,
                                    'sales_purchase' => $sales_count,
                                    'likes' => $likes_count,
                                    'posts' => $return_arr,
                                ]);
                            } else {
                                return response()->json([
                                    'status' => false,
                                    'message' => 'غير قادر على العثور على المستخدم الحالي',
                                ]);
                            }
                        } else {
                            return response()->json([
                                'status' => false,
                                'message' => 'غير قادر على العثور على المستخدم',
                            ]);
                        }
                        return response()->json(['status' => true, 'user' => $user]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'حدث خطأ']);
                    }
                // }
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => $e->getMessage()]);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'هوية مستخدم غير صالحه']);
        }
    }

    public function share_count(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);

        $user = User::find($request->user_id);
        $user->shares = (int) $user->shares + 1;
        if ($user->save()) {
            return response()->json(['status' => true, 'message' => 'Share Updated']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
    }

    public function get_share()
    {
        $share = db::select(db::raw("SELECT users.name , users.image, posts.image , shares.* FROM `shares`
join users on users.id = shares.user_id
join posts on posts.id = shares.post_id"));
        if ($share) {
            return response()->json($share);
        } else {
            return response()->json(['message' => 'User is not share a post']);
        }
    }

    public function profile(Request $request)
    {
        $request->validate(['user_id' => 'required', 'current_user_id' => 'required']);
        $user = User::find($request->current_user_id);
     
        

        
        if ($user) {
            $current = User::find($request->current_user_id);
            if ($current) {
                $followers = UserFollower::where('follower_id', $user->id)->get();
                $follower_count = $followers->count();

                $following = UserFollower::where('user_id', $user->id)->get();
                $following_count = $following->count();

                $likes = PostLike::where('user_id', $user->id)->get();
                $likes_count = $likes->count();

                $bids_count = 0;

                $posts = Post::where('category_id', 2)->orderBy('id', 'desc')->where('user_id', $request->user_id)->get();
                foreach ($posts as $post) {
                    $bids_count = $bids_count + $post->bids->count();
                }

                $shares = $user->shares;

                $following_user = UserFollower::where('follower_id', $user->id)->where('user_id', $current->id)->first();
                if ($following_user) {
                    $following_user = true;
                } else {
                    $following_user = false;
                }

                $sales = Sale::where('seller_id', $user->id)->orWhere('purchaser_id', $user->id)->get();
                $sales_count = $sales->count();

                $posts = Post::where('user_id', $user->id)->withCount('likes')->withCount('comments')->get();

                $return_arr = array();

                if ($posts->count() > 0) {
                    foreach ($posts as $post) {
                        $category = $post->category;
                        $user = $post->user;

                        $img_array = [];
                        foreach ($post->images as $post_img) {
                            array_push($img_array, $post_img->image);
                        }
                        $post->img = $img_array;

                        $liked = false;

                        $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
                        if ($check) {
                            $liked = true;

                        }

                        $return_arr[] = array('post' => $post, 'flagForLike' => $liked);

                    }
                }
                
             
                
        
          

                return response()->json([
                    'status' => true,
                    'user' => $user,
                    'follow_status' => $following_user,
                    'follwers' => $follower_count,
                    'following' => $following_count,
                    'offers' => $bids_count,
                    'shares' => $shares,
                    'sales_purchase' => $sales_count,
                    'likes' => $likes_count,
                    'posts' => $return_arr,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'غير قادر على العثور على المستخدم الحالي',
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'غير قادر على العثور على المستخدم',
            ]);
        }
    }

    public function categories()
    {
        $categories = Category::all();
        return response()->json(['status' => true, 'categories' => $categories]);
    }

    public function post_camelClub(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "location" => "required",
            "description" => "required",
            "images" => "required",

        ]);

        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = '';
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->camel_type = '';
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 1;

        $post->save();

        // foreach($request->images as $image)
        // {
        // $imageName =  $request->file('images')->store('/images/posts');
        // $image = str_replace(' ', '+', $image);
        // $imageName = "post" . md5(rand(100, 1000)) . rand(100, 1000) . ".jpeg";
        // Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));

        $image_64 = $request->images; //your base64 encoded data

        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

        $image = str_replace($replace, '', $image_64);

        $image = str_replace(' ', '+', $image);

        $imageName = Str::random(10) . '.' . $extension;

        Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));

        $image = new PostImage();
        $image->post_id = $post->id;
        $image->image = $imageName;
        $post->image = $imageName;

        $post->save();
        $image->save();

        // }
        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);

    }

    public function post_Club(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => "required",
            "title" => "required",
            "location" => "required",
            "description" => "required",
            // "images" => "required",
            // "video" => "required",
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = '';
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->camel_type = '';
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 1;
        $post->save();
        
        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        
        if ($request->video && $request->video!='null') {
            $image_64 = $request->video;
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $video = str_replace($replace, '', $image_64);
            $video = str_replace(' ', '+', $video);
            $videoName = Str::random(10) . '.' . $extension;
            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));
            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }

        $imagee = array();
       
        if ($file = $request->images) {
            foreach ($file as $image_64) {
                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();

            }
        }
        
        if($request->video && $request->video!='null'){
             if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }
        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
        //   return $post;

    }

  public function view_post(Request $request)
    {
       
         
        $post_likes_ = '';
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
        $posts = db::select(db::raw("select posts.*,posts.id as post_id,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.phone as user_phonee,categories.name as category_name,users.chat_status as chat_status, users.image as user_images,users.name as name
        from posts
        join users on users.id = posts.user_id
        join categories on categories.id= posts.category_id where posts.status = 1
        order by posts.id desc"));
    
        $return_arr = array();
        foreach ($posts as $imgs) {
            // $id = $imgs->id;
            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();

            $id = $imgs->post_id;
            $bid_status = $imgs->bid_status;
            $user_id = $imgs->user_id;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phonee;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $bid_price = $highest_bid;
            
            $name = $imgs->name;
            $flagForLike= false;
            if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
                }
            }
            }
            
            
            $arr = explode(",", $imgs->image);
            $bid_price = 0;
            $bid_status = $imgs->bid_status;

            

            if($category_id == 2 || $category_id == 6 || $category_id == 8)
            {
               $post_id = $imgs->post_id;
               $post_bid = PostBid::where('post_id', $post_id)->first();
               
               
               if(!empty($post_bid))
               {
                  $bid_price = $post_bid->price; 
               }
         
               
              
            if($price_type == "سوم")
            {
                
                $bid_expired_days = $imgs->bid_expired_days;
                $string = $bid_expired_days;
                $expired_days = preg_replace('/\D/', '', $string); 
                
                $created_at = $imgs->created_at;
                $current_date = date('Y-m-d H:i:s');
                $created_at_timestamp = strtotime($created_at);
                $created_at_plus_days = date('Y-m-d H:i:s', strtotime('+'.$expired_days.' days', $created_at_timestamp));
                
                $datetime1 = date_create($created_at_plus_days);
                $datetime2 = date_create($current_date);
                $interval = date_diff($datetime1, $datetime2);
                
                $days_difference = $interval->format('%a');
               
                // if($created_at_plus_days == $current_date || $current_date > $created_at_plus_days ){
                
                //      $bid_status = 1;
                // }
            }
                 
             if($price_type == "حد"){
                 $bid_status = $imgs->bid_status;
             }
               
            }        
            $return_arr[] = array(
                'img' => $arr, 'id' => $id,'bid_status'=>$bid_status,'thumbnail'=>$thumbnail, 'user_id' => $user_id,'chat_status'=>$chat_status, 'user_name' => $user_name, 
                'user_phone' => $user_phone,'bid_price'=> $highest_bid,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'bid_price'=>$highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function fetch_user($user_id)
    {

        $user = User::find($user_id);
        // $Posting=Post::where('user_id',$user->id)->get();
        // $counting_post= count($Posting);
        // if($counting_post > 0 && $counting_post<100 ){
        // $user->subscription="normal";
        // }else if ($counting_post > 100 && $counting_post<250){
        // $user->subscription="special";    
        // }else{
        // $user->subscription="famous";    
        // }
        // $user->save();
        
        
        $post_likes_ = '';
        $post_likes_ = PostLike::where('user_id',$user_id)->get();
        if ($user) {
            $current = User::find($user->id);
            if ($current) {
                $followers = UserFollower::where('follower_id', $user->id)->get();
                // counting of followers
                $follower_count = $followers->count();
                 
                $following = UserFollower::where('user_id', $user->id)->get();
                // counting of followers
                $following_count = $following->count();

                $likes = PostLike::where('user_id', $user->id)->get();
                // couting of likes
                $likes_count = $likes->count();

                $bids_count = 0;
                

                $postss = Post::where('category_id', 2)->orderBy('id', 'desc')->where('user_id', $user->id)->get();
             
                foreach ($postss as $post) {
                $bids_count = $bids_count + $post->bids->count();
                }

                $shares = $user->shares;
                $following_user = UserFollower::where('follower_id', $user->id)->where('user_id', $current->id)->first();
                if ($following_user) {
                    $following_user = true;
                } else {
                    $following_user = false;
                }

                $sales = Sale::where('seller_id', $user->id)->orWhere('purchaser_id', $user->id)->get();
                $sales_count = $sales->count();

                // $posts = Post::where('user_id', $user->id)->withCount('likes')->withCount('comments')->get();
                $posts = db::select(db::raw("select posts.*,posts.id as post_id,categories.name as category_name, users.image as user_images,users.name as name
                                             from posts
                                             join users on users.id = posts.user_id
                                             join categories on categories.id= posts.category_id
                                             where posts.status = 1 and posts.user_id = $user_id
                                             order by posts.id desc"));
                $return_arr = array();
                if (count($posts) > 0) {
             foreach ($posts as $imgs) {
            // $id = $imgs->id;
            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
            $thumbnail = DB::table('post_thumbnail')->where('post_id', $imgs->id)->select('thumbnail')->first();

            $id = $imgs->post_id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phone;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $name = $imgs->name;
            $flagForLike= false;
            if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            
            
            $arr = explode(",", $imgs->image);
            
            $bid_price = 0;
            
            if($category_id == 2 || $category_id == 6 || $category_id == 8){
                
                $post_id = $imgs->post_id;
                $post_bid = PostBid::where('post_id', $post_id)->first();
                
                if(!empty($post_bid))
                {
                    
                        $bid_price = $post_bid->price;
                    
                }
                
            }
       
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'user_id' => $user_id,'thumbnail'=>$thumbnail, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'bid_price'=>$highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
            );
        }
            
                }
                
                
                if($user->subscription=='normal'){
                $user['subscription']='عضو' ;   
                } 
                else if($user->subscription=='vip'){
                $user['subscription']='عضو مهم' ;   
                } 
                else if($user->subscription=='famous'){
                $user['subscription']='عضو مميز' ;   
                } 

                return response()->json([
                    'status' => true,
                    'user' => $user,
                    'follow_status' => $following_user,
                    'follwers' => $follower_count,
                    'following' => $following_count,
                    'offers' => $bids_count,
                    'shares' => $shares,
                    'sales_purchase' => $sales_count,
                    'likes' => $likes_count,
                    'posts' => $return_arr,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'غير قادر على العثور على المستخدم الحالي',
                ]);
            }
           
        }

        return $user;
    }

    public function post_camelSelling(Request $request)
    {
         
        
   

        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "color" => "required",
            "camel_type" => "required",
            "location" => "required",
            "description" => "required",
            // "images" => "required",
            //  "video" => "required",
            "price_type" => "required",
            "price" => "required",
            "register" => "required",
            "commission" => "required",
            // "thumbnail" => "required",
            // 'bid_expired_days'=>'required'
        ]);
           
        
        $string = $request->bid_expired_days;
        $expired_days = preg_replace('/\D/', '', $string);
       
        
        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = $request->color;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->camel_type = $request->camel_type;
        $post->price_type = $request->price_type;
        $post->price = $request->price;
        $post->register = $request->register;
        $post->bid_expired_days = $request->bid_expired_days;
        $post->expired_days = $expired_days;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 2;
        $post->commission = $request->commission;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        // Log::info('Add selling image logs');
        // Log::info($request->images);

        /**************************************************
         *                     VIDEOS
         * *************************************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
            
            
            
        }
        }

        /**************************************************
         *                       IMAGES
         * ***********************************************/
        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
        if ($request->has('thumbnail')) {
                // Decode the JSON data from the thumbnail field
                $thumbnailData = json_decode($request->input('thumbnail'));
    
                // Extract information from the decoded JSON data
                $path = $thumbnailData->path;
                $mime = $thumbnailData->mime;
                $size = $thumbnailData->size;
    
                // Extract the image extension from the mime type
                $extension = explode('/', $mime)[1];
    
                // Generate a unique image name
                $imageName = Str::random(3) . '-' . time() . '.' . $extension;
    
                // Store the image in the public disk under 'images/thumbnail'
                Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));
    
                // Save additional information in the database
                $postThumbnail = new PostThumbnail;
                $postThumbnail->thumbnail = $imageName;
                $postThumbnail->post_id = $post->id;
              
                $postThumbnail->save();
    
              
            }
        }    

      
    
      return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
}
    public function post_camelMissing(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "color" => "required",
            "camel_type" => "required",
            "location" => "required",
            "description" => "required",
            // "images" => "required",
            // "video" => "required"
        ]);

        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = $request->color;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->camel_type = $request->camel_type;

        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 3;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        /*************************** Video ******************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
        }

        /***************************** Image ******************************/

        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }

        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function post_camelTreatment(Request $request)
    {
        
        $request->validate([
            "user_id" => "required",
            // "images" => "required",
            "title" => "required",
            "color" => "required",
            "camel_type" => "required",
            "location" => "required",
            "description" => "required",
            // "video" => "required"
        ]);

        $user = User::find($request->user_id);

        $post = new Post();
        $post->title = $request->title;
        $post->color = $request->color;
        $post->camel_type = $request->camel_type;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->price = '';

        $post->user_phone = $user->phone;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 4;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        /************************* Videos ******************************************/
         if($request->video && $request->video!='null'){
         if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
         }  

        /****************************** Images ***************************************/
        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }

        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function post_camelFood(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "color" => "required",
            "camel_type" => "required",
            "location" => "required",
            "description" => "required",
            "price_type" => "required",
            "price" => "required",
            // "register" => "required",
            // "images" => "required",
            // "video"          => "required"
        ]);

        $string = $request->bid_expired_days;
        $expired_days = preg_replace('/\D/', '', $string);
        
        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = $request->color;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->camel_type = $request->camel_type;
        $post->price_type = $request->price_type;
        $post->price = $request->price;
        $post->bid_expired_days = !is_null($request->bid_expired_days) ? $request->bid_expired_days : 0;
        $post->expired_days = $expired_days;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 6;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        /******************************** Videos *********************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
        }
        /************************************ Images **************************************/

        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
        if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }


        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function post_camelEquipment(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "color" => "required",
            "camel_type" => "required",
            "location" => "required",
            "description" => "required",
            "price_type" => "required",
            "price" => "required",
            // "register" => "required",
            // "images" => "required",
            // "video" => "required"
        ]);
        
        $string = $request->bid_expired_days;
        $expired_days = preg_replace('/\D/', '', $string);
        
        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;
        $post->color = $request->color;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = isset($request->phone) ? $request->phone : '';
        $post->camel_type = $request->camel_type;
        $post->price_type = $request->price_type;
        $post->price = $request->price;
        $post->bid_expired_days = !is_null($request->bid_expired_days) ? $request->bid_expired_days : 0;
        $post->expired_days = $expired_days;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 8;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        /****************************************** Videos ***********************************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
        }

        /**********************************************Images***********************************************/

        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }

   

        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function post_camelCompetition(Request $request)
    {
       

         

        $request->validate([
            "user_id" => "required",
            "title" => "required",
            // "color"=>"required",
            //  "camel_type"=>"required",
            "location" => "required",
            "description" => "required",
            "competition_id" => "required",
            // "images" => "required",
            "age" => "required",
            // "video" => "required"

        ]);
        
             

        DB::beginTransaction();
        try {
            $user = User::find($request->user_id);
            
            if ($user) {
                $post = new Post();
                $post->title = $request->title;
                $post->location = $request->location;
                $post->description = $request->description;
                $post->competition_id = $request->competition_id;
                $post->age = $request->age;

                $post->user_phone = (isset($request->phone) && !empty($request->phone)) ? $request->phone : '';
                $post->user_email = !is_null($user->email) ? $user->email : '';
                $post->user_name = $user->name;
                $post->user_id = $user->id;
                $post->date = date('Y-m-d');
                $post->category_id = 7;
                if($post->save())
                {
                    $competition_participants = new CompetitionParticipant();
                    $competition_participants->user_id = $request->user_id;
                    $competition_participants->competition_id = $request->competition_id; 
                    $competition_participants->save();
                }
        

                $notification = new Notification();
                $notification->description = "تمت إضافة رسالتك بنجاح";
                $notification->sender_id = $user->id;
                $notification->post_id = $post->id;
                $notification->save();
                 
              
                /*************************** Video ******************************/
                if($request->video && $request->video!='null'){
                if ($image_64 = $request->video) {
                    // $path = $request->file('video')->store('videos');
                    $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                    $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                    $video = str_replace($replace, '', $image_64);

                    $video = str_replace(' ', '+', $video);

                    $videoName = Str::random(10) . '.' . $extension;

                    Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

                    $post_video = new PostVideo();
                    $post_video->post_id = $post->id;
                    $post_video->video = $videoName;
                    $post->video = $videoName;
                    $post_video->save();
                    $post->save();
                }
                }
                
                
                     
                         
                    if($request->video && $request->video!='null'){
                         if ($request->has('thumbnail')) {
                            // Decode the JSON data from the thumbnail field
                            $thumbnailData = json_decode($request->input('thumbnail'));
           
                
                            // Extract information from the decoded JSON data
                            $path = $thumbnailData->path;
                            $mime = $thumbnailData->mime;
                            $size = $thumbnailData->size;
                
                            // Extract the image extension from the mime type
                            $extension = explode('/', $mime)[1];
                
                            // Generate a unique image name
                            $imageName = Str::random(3) . '-' . time() . '.' . $extension;
                
                            // Store the image in the public disk under 'images/thumbnail'
                            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));
                
                            // Save additional information in the database
                            $postThumbnail = new PostThumbnail;
                            $postThumbnail->thumbnail = $imageName;
                            $postThumbnail->post_id = $post->id;
                          
                            $postThumbnail->save();
                
                          
                        }

                        }
                     
                /***************************** Image ******************************/

                $imagee = array();
                
                if ($file = $request->images) {

                    foreach ($file as $image_64) {

                        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                        $image = str_replace($replace, '', $image_64);

                        $image = str_replace(' ', '+', $image);

                        $imageName = Str::random(10) . '.' . $extension;

                        Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                        // $imageName= $imageName.$newname.",";
                        $imagee[] = $imageName;

                        $image = new PostImage();
                        $image->post_id = $post->id;
                        $image->image = implode(',', $imagee);
                        $post->image = implode(',', $imagee);

                }
                }
                
              
                    

                /** winner  */

                $winner = new CompetitionWinner();
                if ($request->user_id == $winner->user_id && $request->competition_id == $winner->competition_id) {
                    return response()->json(['status' => 'User is already exist in competition list']);
                } else {
                    $winner->competition_id = $post->competition_id;
                    $winner->post_id = $post->id;
                    $winner->user_id = $post->user_id;
                    $winner->save();

                    $post->save();
                    if($request->images){
                    $image->save();
                    }
                }
              

                DB::commit();
                return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
            } else {
                return response()->json(['status' => false, 'message' => 'هوية مستخدم غير صالحه']);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function add_moving(Request $request)
    {

        $request->validate([
            "user_id" => "required",
            "first_name" => "required",
            "car" => "required",
            "type" => "required",
            "to" => "required",
            "from" => "required",
            "description" => "required",
            "price" => "required",
            // "images" => "required",
        ]);

        $user = User::find($request->user_id);
        $moving = new Moving();
        $moving->user_id = $user->id;
        $moving->user_name = $request->first_name;
        $moving->description = $request->description;
        $moving->phone = '';
        $moving->location = $request->from;
        $moving->to_location = $request->to;
        $moving->type = $request->type;
        $moving->car_model = $request->car;
        $moving->price = $request->price;
        $moving->save();

      

        $image_64 = $request->images; //your base64 encoded data
        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image = str_replace($replace, '', $image_64);
        $image = str_replace(' ', '+', $image);
        $imageName = Str::random(10) . '.' . $extension;
        Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
        $image = new PostImage();
        $image->moving_id = $moving->id;
        $image->image = $imageName;
        $image->save();
        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function follow(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'follower_id' => 'required',
        ]);
        $check = UserFollower::where('user_id', $request->user_id)->where('follower_id', $request->follower_id)->first();
        if ($check) {
            if ($check->delete()) {
                return response()->json(['status' => true, 'message' => 'Successfully unFollowed']);
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        } else {
            if ($request->user_id != $request->follower_id) {
                $follow = new UserFollower();
                $follow->user_id = $request->user_id;
                $follow->follower_id = $request->follower_id;

                if ($follow->save()) {
                    return response()->json(['status' => true, 'message' => 'Successfully Followed']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Error Occurred']);
                }
            } else {
                return response()->json(['ERROR' => 'UserId and FollowerId are same']);
            }
        }
    }

    public function unfollow(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'follower_id' => 'required',
        ]);

        $check = UserFollower::where('user_id', $request->user_id)->where('follower_id', $request->follower_id)->get();
        if ($check->count() <= 0) {
            return response()->json(['status' => false, 'message' => 'You did not follow this person']);
        } else {
            $follow = UserFollower::where('user_id', $request->user_id)->where('follower_id', $request->follower_id)->first();

            if ($follow->delete()) {
                return response()->json(['status' => true, 'message' => 'Successfully UnFollowed']);
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        }
    }

    public function ordinal($number)
    {
        $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }
    
        public function get_competition()
    {

        $competitions = DB::select(DB::raw("SELECT * FROM competitions ORDER BY id DESC"));
        $data = [];
        foreach ($competitions as $competition) {
            $competition_posts = DB::select(DB::raw("
                SELECT competition_id, comment_count as comment_count
                FROM posts
                LEFT JOIN users ON users.id = posts.user_id
                WHERE competition_id = $competition->id
                GROUP BY competition_id
                ORDER BY posts.id DESC
            "));
            
            $competition->comment_count = (!empty($competition_posts) ? $competition_posts[0]->comment_count : 0);
            $data[] = $competition;
        }
        return response()->json(['status' => $data]);
    }
    // public function get_competition()
    // {

    //     $competition = db::select(db::raw("Select * from competitions order by id desc"));
    //     return response()->json(['status' => $competition]);
    //     //         $return_arr = array();
    //     //      foreach ($competition as $imgs) {
    //     //          $id = $imgs->id;
    //     //          $user_id = $imgs->user_id;
    //     //          $user_name = $imgs->user_name;
    //     //          $user_phone = $imgs->user_phone;
    //     //          $user_email = $imgs->user_email;
    //     //          $category_id = $imgs->category_id;
    //     //          $to_location = $imgs->to_location;
    //     //          $title = $imgs->title;
    //     //          $location = $imgs->location;
    //     //          $color = $imgs->color;
    //     //          $camel_type = $imgs->camel_type;
    //     //          $activity = $imgs->activity;
    //     //          $car_model = $imgs->car_model;
    //     //          $car_type = $imgs->car_type;
    //     //          $price = $imgs->price;
    //     //          $price_type = $imgs->price_type;
    //     //          $date = $imgs->date;
    //     //          $video = $imgs->video;
    //     //          $age = $imgs->age;
    //     //          $description = $imgs->description;
    //     //          $competition_id = $imgs->competition_id;
    //     //          $register = $imgs->register;
    //     //          $account_activity = $imgs->account_activity;
    //     //          $status = $imgs->status;
    //     //          $moving_camel_amount = $imgs->moving_camel_amount;
    //     //          $view_count = $imgs->view_count;
    //     //          $share_count = $imgs->share_count;
    //     //          $like_count = $imgs->like_count;
    //     //          $comment_count = $imgs->comment_count;
    //     //          $created_at = $imgs->created_at;
    //     //          $updated_at = $imgs->updated_at;
    //     //          $commission = $imgs->commission;

    //     //         $category_name  =$imgs->category_name;
    //     //         $user_images = $imgs->user_images;
    //     //         $name = $imgs->name;

    //     //         $arr = explode(",", $imgs->image);
    //     //         $return_arr[] = array('img' => $arr, 'id'=>$id , 'user_id'=> $user_id, 'user_name'=> $user_name, 'user_phone'=> $user_phone,
    //     //     'user_email'=> $user_email, 'category_id'=>$category_id, 'to_location'=> $to_location, 'title'=> $title, 'location'=> $location,
    //     //   'color'=> $color, 'camel_type'=> $camel_type, 'activity'=> $activity, 'car_model'=>$car_model, 'car_type'=> $car_type, 'price'=> $price,
    //     //   'price_type'=> $price_type, 'date'=> $date, 'video' => $video, 'age'=> $age, 'description'=> $description, 'competition_id'=> $competition_id,
    //     //   'register'=> $register, 'account_activity'=> $account_activity, 'status'=> $status, 'moving_camel_amount'=> $moving_camel_amount, 'view_count'=> $view_count,
    //     //   'share_count'=> $share_count, 'like_count'=>$like_count, 'comment_count'=> $comment_count, 'created_at'=> $created_at, 'updated_at'=> $updated_at, 'commission'=> $commission
    //     //   ,'category_name' => $category_name, 'user_images'=> $user_images, 'name'=> $name);
    //     //  }

    //     //      return response()->json(['Posts'=> $return_arr]);
    // }
    
    
    public function get_competition_details(Request $request)
    {
        $request->validate([
            'competition_id' => 'required',
            ]);
            
        $array=[];
          $post_likes_='';
            if($request->user_id)
             {
                  $post_likes_ = PostLike::where('user_id',$request->user_id)->get();
              }
        $competition = db::select("Select  * from competitions where competitions.id = $request->competition_id");
        $currentDate = date('Y-m-d');
        $competition_winner = [];
        if($competition[0]->end_date < $currentDate)
        {
            // $competition_winner = db::select("SELECT users.image as user_image, users.name as user_name, users.id as user_id,max(posts.like_count) as like_count,max(posts.comment_count) as comment_count,max(posts.view_count) 
            //                                     as view_count FROM `competitions` join posts on 
            //                                     posts.competition_id = competitions.id
            //                                     join competition_winners on competition_winners.competition_id= competitions.id
            //                                     join users on users.id = competition_winners.user_id
            //                                     WHERE competitions.id= $request->competition_id"); 
            
            // $competition_winner_user_id = DB::table('posts')->where('competition_id', $request->competition_id)->select('user_id','like_count','comment_count','view_count')->orderBy('like_count', 'desc')->orderBy('comment_count', 'desc')->orderBy('view_count', 'desc')->first();            
           $check_compition_data=DB::table('compitition_winners')->get();
           $compitition_ids=[];
           if($check_compition_data){
               foreach($check_compition_data as $cd){
                $compitition_ids[]=$cd->competition_id;   
               }
           }
           if(in_array($request->competition_id,$compitition_ids)){
            $competition_winner_user_id = DB::table('compitition_winners')
            ->where('competition_id', $request->competition_id)
            ->select('user_id', 'like_count', 'comment_count', 'view_count')
            ->first();
            
               
               
           }else{
             $competition_winner_user_id = DB::table('posts')
            ->where('competition_id', $request->competition_id)
            ->select('user_id', 'like_count', 'comment_count', 'view_count')
            ->orderBy('like_count', 'desc')
            ->orderBy('comment_count', 'desc')
            ->orderBy('view_count', 'desc')
            ->first();   
            $compitition_winner_data=DB::table('compitition_winners')->insert([
                'like_count'=>$competition_winner_user_id->like_count,
                'comment_count'=>$competition_winner_user_id->comment_count,
                'view_count'=>$competition_winner_user_id->view_count,
                'competition_id'=>$request->competition_id,
                'user_id'=>$competition_winner_user_id->user_id
            ]);               
           }
           

          
            
            $competition_winner = User::where('id', $competition_winner_user_id->user_id)
            ->select('image as user_image', 'name as user_name', 'id as user_id')
            ->first();
            $array=[];
            $competition_winner['like_count'] = $competition_winner_user_id->like_count;
            $competition_winner['comment_count'] = $competition_winner_user_id->comment_count;
            $competition_winner['view_count'] = $competition_winner_user_id->view_count;
            array_push($array,$competition_winner);

          

                                                    
            /*
            
            SELECT cw.user_id, MAX(posts.like_count) AS like_count
                                                FROM competition_winners cw
                                                JOIN posts ON cw.post_id = posts.id
                                                WHERE cw.competition_id = $request->competition_id
                                                GROUP BY cw.user_id
                                                HAVING MAX(posts.like_count) = (
                                                    SELECT MAX(posts.like_count)
                                                    FROM competition_winners cw_inner
                                                    JOIN posts ON cw_inner.post_id = posts.id
                                                    WHERE cw_inner.competition_id = $request->competition_id 
            
            foreach($competition_winner as $competition_winners){
                
                $user_detail = User::where('id',$competition_winners->user_id)->first();
                $user_name = $user_detail->name;
                $user_iamge = $user_detail->image;
                $user_id = $user_detail->id;
                $like_count = $competition_winners->like_count;
                $competition_winner_[]= array('user_name'=>$user_name,'user_iamge'=>$user_iamge,'user_id'=>$user_id, 'like_count'=>$like_count);
                
            }    */                                    
        }
        
        
        
        
        
        
        $sponsors = db::select("Select competition_sponsor.*, sponsors.* from competition_sponsor JOIN sponsors on competition_sponsor.sponsor_id = sponsors.id where competition_sponsor.competition_id = $request->competition_id");
        
        $competition_prize = db::select("select * from competition_prizes WHERE competition_prizes.competition_id =$request->competition_id ");
       
        $competition_posts = db::select("SELECT *,posts.id as post_id,users.phone as user_phonee,users.whatsapp_no as whatsapp_no,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.image as user_image, posts.image as post_img FROM `posts` 
                                            left join users on users.id = posts.user_id
                                            where competition_id = $request->competition_id order by posts.id desc");
       
        
        $competition_participants = DB::select(DB::raw("SELECT DISTINCT users.name as user_name, users.image as user_image, users.id as user_id FROM posts LEFT JOIN users ON users.id = posts.user_id WHERE competition_id = $request->competition_id ORDER BY posts.id DESC"));
                               
        if(!empty($competition_posts))
        {
              foreach ($competition_posts as $imgs) {
            // $id = $imgs->id;
              $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->post_id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $post_id = $imgs->post_id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phonee;
            $user_image = $imgs->user_image;
            $user_email = $imgs->user_email;
            $whatsapp_no = $imgs->whatsapp_no;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $is_approved = $imgs->is_approved;
            $flagForLike= false;
            if(!empty($post_likes_)){ 
            foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $post_id) {
                 $flagForLike = true;
              
                }
            }
            }
            
            $arr = explode(",", $imgs->post_img); 
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'post_id'=> $post_id,'thumbnail'=>$thumbnail,'user_id' => $user_id, 'user_name' => $user_name, 'user_image'=>$user_image,'whatsapp_no'=>$whatsapp_no,
                'user_phone' => $user_phone,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                'commission' => $commission, 'is_approved'=>$is_approved,'flagForLike'=>$flagForLike
            );
            
            
        }
      
        
           $competition_participant = array();
              foreach($competition_participants as $participants){
                  
                  $user_id = $participants->user_id;
                  $user_name = $participants->user_name;
                  $user_image = $participants->user_image;
                  
                  $competition_participant[]= array('user_id'=>$user_id, 'user_name'=>$user_name, 'user_image'=>$user_image);
              }
        
        $data = array('competition' => $competition, 'competition_winner' => $array, 'sponsors' => $sponsors, 'competition_prize' => $competition_prize, 'competition_participant'=>$competition_participant, 'competition_posts' => $return_arr);
       
        return response()->json($data);
        
        }else
        {
             
              $data = array('competition' => $competition, 'competition_winner' => $array ? $array:[], 'sponsors' => $sponsors, 'competition_prize' => $competition_prize, 'competition_posts' => null);
              return response()->json($data);
        }
      
    }
    
    

    public function get_moving()
    {
        $moving = db::select(db::raw("Select * from movings"));
        return response()->json($moving);
    }
    public function accept_bid(Request $request,$id)
    {
     
        $post_bid = PostBid::where('id', $id)
                   ->where('bid_position', 0)
                   ->first();
        if(!$post_bid){
            return response()->json([
            'message' => 'لعرض مغلق بالفعل!',
            'status' => 'error'
            ]);

        }
        $post_bid->bid_position=1;
        $post_bid->save();
        
        $user=DB::table('users')->where('id',$post_bid->user_id)->first();
            DB::table('posts')
            ->where('id', $post_bid->post_id) 
            ->update([
            'bid_status' => 1,
            'bid_is_expired' => 'bid is expire'
            ]);
        
         $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$user->device_token.'",
                 "notification" : {
                     "body": "'.$user->name.'هانينا! لقد فزت بالمزايدة بنجاح!"
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: key='.$this->firebase_key.''
                  ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                return response()->json([
               'message' => 'بنجاح. ' . $user->name . ' تم منح العطاء',
                'status' => 'success'
                ]);
                }


    public function get_competitions($user_id)
    {
        $today = Date('Y-m-d');
        $return_arr = array();
        $competition = Competition::where('end_date', '>', $today)->where('status', 1)->first();
        $winner_arr = array();
        if ($competition) {
            $sponsor = $competition->sponsors;
            $winners = $competition->winners;
            foreach ($winners as $winner) {
                $user = $winner->user;
            }
            $posts = Post::where('competition_id', $competition->id)->orderBy('id', 'desc')->where('status', 1)->withCount('likes')->withCount('comments')->get();

            foreach ($posts as $post) {
                $category = $post->category;
                $user = $post->user;
                $images = $post->images;
                $liked = false;
                $share = 0;
                $view = 0;

                $check = PostLike::where('post_id', $post->id)->where('user_id', $user_id)->first();

                if ($check) {
                    $liked = true;
                }

                foreach ($winners as $winner) {

                    $position = $this->ordinal($winner->position);

                    $winner_arr[] = array(
                        'position' => $position,
                        'user' => $winner->user,
                    );
                }

                $return_arr[] = array('post' => $post, 'liked' => $liked, 'share' => $share, 'views' => $view);
            }
        }

        return response()->json(['status' => true, 'competition' => $competition, 'posts' => $return_arr, 'winners' => $winner_arr]);
    }

    public function get_camelClub(Request $request)
    {

        $moving = db::select(db::raw("Select posts.*, users.name as name, users.image as  user_image, categories.name as category_name from posts
          JOIN categories ON categories.id= posts.category_id
          JOIN users on users.id= posts.user_id
          where category_id = 1
          ORDER by posts.id DESC"));
        return response()->json($moving);

        // $request->validate([
        //     'user_id' => 'required'
        // ]);

        // if($request->user_id == 0){
        //     $posts=Post::where('category_id',1)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;
        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;

        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);
        // }else{

        //     $followings=UserFollower::where('user_id',$request->user_id)->get();

        //     $following_array=array();
        //     foreach($followings as $following){
        //         $following_array[] = $following->follower_id;
        //     }
        //     $following_array[] = $request->user_id;

        //     $posts=Post::where('category_id',1)->whereIn('user_id',$following_array)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;

        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;

        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);

        // }

    }
    public function get_camelSelling(Request $request)
    {
         
         $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
        
        $moving = db::select(db::raw("Select posts.*,posts.id as post_id,users.phone as user_phonee,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.name as name, users.image as  user_image, categories.name as category_name from posts
                                      JOIN categories ON categories.id= posts.category_id
                                      JOIN users on users.id= posts.user_id
                                      where category_id = 2 ORDER by posts.id DESC "));
        $return_arr = array();
        foreach ($moving as $imgs) {
            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
              $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $bid_price = $highest_bid;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $bid_status = $imgs->bid_status;
            $user_phone = $imgs->user_phonee;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            
            $arr = explode(",", $imgs->image);
            
            $post_id= $imgs->post_id;
            $bid_price = 0;
            
            $post_bid= PostBid::where('post_id',$post_id)->first();
            
            if(!empty($post_bid))
            {
                $bid_price = $post_bid->price;
            }
            
            $bid_status = $imgs->bid_status;
            if($price_type == "سوم")
            {
                
                $bid_expired_days = $imgs->bid_expired_days;
                $string = $bid_expired_days;
                $expired_days = preg_replace('/\D/', '', $string); 
                
                $created_at = $imgs->created_at;
                $current_date = date('Y-m-d H:i:s');
                $created_at_timestamp = strtotime($created_at);
                $created_at_plus_days = date('Y-m-d H:i:s', strtotime('+'.$expired_days.' days', $created_at_timestamp));
                
                $datetime1 = date_create($created_at_plus_days);
                $datetime2 = date_create($current_date);
                $interval = date_diff($datetime1, $datetime2);
                
                $days_difference = $interval->format('%a');
                //  dd($current_date .' > '. $created_at_plus_days );
                 
                
                if($created_at_plus_days == $current_date || $current_date > $created_at_plus_days ){
                // dd('hdkf');
                     $bid_status = 1;
                }
            }
             if($price_type == "حد"){
                 $bid_status = $imgs->bid_status;
             }
            
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'thumbnail'=>$thumbnail,'bid_status'=>$bid_status,'user_id' => $user_id, 'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'bid_price'=>$highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name, 'flagForLike'=> $flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);

        // $request->validate([
        //     'user_id' => 'required'
        // ]);

        // if($request->user_id==0){
        //     $posts=Post::where('category_id',2)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();
        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $bids = $post->bids;
        //         foreach($bids as $bid){
        //             $bid->user;
        //         }
        //         $bid = $post->getLastBid();
        //         if($bid){

        //             $user = $bid->user;
        //         }

        //         $images = $post->images;
        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;

        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked,'last_bid'=>$bid);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);
        // }else{
        //     $followings=UserFollower::where('user_id',$request->user_id)->get();

        //     $following_array=array();
        //     foreach($followings as $following){
        //         $following_array[] = $following->follower_id;
        //     }
        //     $following_array[] = $request->user_id;

        //     $posts=Post::where('category_id',2)->whereIn('user_id',$following_array)->where('status',1)->orderBy('id','desc')->withCount('likes')->withCount('comments')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;
        //         $bids = $post->bids;
        //         foreach($bids as $bid){
        //             $bid->user;
        //         }
        //         $bid = $post->getLastBid();

        //         if($bid){

        //             $user = $bid->user;
        //         }
        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;
        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked,'last_bid'=>$bid);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);
        // }

    }

    public function get_camelMissing(Request $request)
    {   
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
            
        $post_likes_ = PostLike::where('user_id',$request->user_id)->get(); 

        $moving = db::select(db::raw("Select posts.*,users.phone as user_phonee, users.name as name,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.image as  user_image, categories.name as category_name from posts
          JOIN categories ON categories.id= posts.category_id
          JOIN users on users.id= posts.user_id
        where category_id = 3 ORDER by posts.id DESC"));
        $return_arr = array();
        foreach ($moving as $imgs) {
              $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phonee;
            $user_email = $imgs->user_email;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;

            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            
            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'thumbnail'=>$thumbnail,'user_id' => $user_id,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 
                'created_at' => $created_at, 'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name,
                'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function get_camelTreatment(Request $request)
    {
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
            
         

        $moving = db::select(db::raw("Select posts.*,users.phone as user_phonee, users.name as name,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.image as  user_image, categories.name as category_name from posts
          JOIN categories ON categories.id= posts.category_id
          JOIN users on users.id= posts.user_id
         where category_id = 4
         ORDER by posts.id DESC"));
        $return_arr = array();
        foreach ($moving as $imgs) {
            $id = $imgs->id;
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_phone = $imgs->user_phonee;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'user_id' => $user_id,'thumbnail'=>$thumbnail,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at,
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,
                'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
        // $request->validate([
        //     'user_id' => 'required'
        // ]);

        // if($request->user_id==0){

        //     $posts=Post::where('category_id',4)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;
        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;

        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);

        // }else{

        //     $followings=UserFollower::where('user_id',$request->user_id)->get();

        //     $following_array=array();
        //     foreach($followings as $following){
        //         $following_array[] = $following->follower_id;
        //     }

        //     $following_array[] = $request->user_id;

        //     $posts=Post::where('category_id',4)->whereIn('user_id',$following_array)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;

        //         $liked=false;

        //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
        //         if($check){
        //             $liked=true;

        //         }

        //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

        //     }
        //     return response()->json(['status' => true, 'posts' => $return_arr]);
        // }

    }

    public function get_camelMoving()
    {
  

        $posts = Moving::orderBy('id', 'desc')->where('status', 1)->get();

        $return_arr = array();
        foreach ($posts as $post) {
            $category = $post->category;
            $user = $post->user;
            $images = $post->images;
        }
        return response()->json(['status' => true, 'posts' => $posts]);
        // }else{
        //     $followings=UserFollower::where('user_id',$request->user_id)->get();

        //     $following_array=array();
        //     foreach($followings as $following){
        //         $following_array[] = $following->follower_id;
        //     }

        //     $following_array[] = $request->user_id;

        //     $posts = Moving::whereIn('user_id',$following_array)->orderBy('id','desc')->get();

        //     $return_arr=array();
        //     foreach($posts as $post){
        //         $category = $post->category;
        //         $user = $post->user;
        //         $images = $post->images;

        //     }
        //     return response()->json(['status' => true, 'posts' => $posts]);
        // }

    }

    public function getcamelFood(Request $request)
    {   
        // $request->validate([
            
        //     'user_id' => 'required'
            
        //     ]);
            
         $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }

        $moving = db::select(db::raw("Select posts.*,posts.id as post_id,users.phone as user_phonee,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.name as name, users.image as  user_image, categories.name as category_name from posts
          JOIN categories ON categories.id= posts.category_id
          JOIN users on users.id= posts.user_id
          where category_id = 6 ORDER by posts.id DESC"));
        $return_arr = array();
        foreach ($moving as $imgs) {
            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $bid_status = $imgs->bid_status;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phonee;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            $flagForLike= false;
            if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            
            $post_id = $imgs->post_id;
            $post_bid = PostBid::where('post_id', $post_id)->first();
            $bid_price = 0;
            if(!empty($post_bid))
            {
                $bid_price = $post_bid->price;
            }
            
            $bid_status = $imgs->bid_status;
            if($price_type == "سوم")
            {
                
                $bid_expired_days = $imgs->bid_expired_days;
                $string = $bid_expired_days;
                $expired_days = preg_replace('/\D/', '', $string); 
                
                $created_at = $imgs->created_at;
                $current_date = date('Y-m-d H:i:s');
                $created_at_timestamp = strtotime($created_at);
                $created_at_plus_days = date('Y-m-d H:i:s', strtotime('+'.$expired_days.' days', $created_at_timestamp));
                
                $datetime1 = date_create($created_at_plus_days);
                $datetime2 = date_create($current_date);
                $interval = date_diff($datetime1, $datetime2);
                
                $days_difference = $interval->format('%a');
                //  dd($current_date .' > '. $created_at_plus_days );
                 
                
                if($created_at_plus_days == $current_date || $current_date > $created_at_plus_days ){
                // dd('hdkf');
                     $bid_status = 1;
                }
            }
             if($price_type == "حد"){
                 $bid_status = $imgs->bid_status;
             }
            
            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail,'bid_status'=>$bid_status, 'user_id' => $user_id, 'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'bid_price'=>$highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at,
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images,
                'name' => $name, 'flagForLike' => $flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function get_camelCompetition(Request $request)
    {

        $request->validate([
            'user_id' => 'required',
        ]);

        if ($request->user_id == 0) {
            $posts = Post::where('category_id', 7)->orderBy('id', 'desc')->where('status', 1)->withCount('likes')->withCount('comments')->get();

            $return_arr = array();
            foreach ($posts as $post) {
                $category = $post->category;
                $user = $post->user;
                $images = $post->images;
                $liked = false;

                $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $liked = true;
                }

                $return_arr[] = array('post' => $post, 'liked' => $liked);
            }
            return response()->json(['status' => true, 'posts' => $return_arr]);
        } else {

            $followings = UserFollower::where('user_id', $request->user_id)->get();

            $following_array = array();
            foreach ($followings as $following) {
                $following_array[] = $following->follower_id;
            }

            $following_array[] = $request->user_id;

            $posts = Post::where('category_id', 7)->whereIn('user_id', $following_array)->orderBy('id', 'desc')->where('status', 1)->withCount('likes')->withCount('comments')->get();
            $return_arr = array();
            foreach ($posts as $post) {
                $category = $post->category;
                $user = $post->user;
                $images = $post->images;

                $liked = false;

                $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $liked = true;
                }

                $return_arr[] = array('post' => $post, 'liked' => $liked);
            }
            return response()->json(['status' => true, 'posts' => $return_arr]);
        }
    }

    public function get_camelEquipment(Request $request)
    {
        $post_likes_ = '';
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
        
        $moving = db::select(db::raw("Select posts.*,users.phone as user_phonee,posts.id as post_id,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status, users.chat_status as chat_status,users.name as name, users.image as  user_image, categories.name as category_name from posts
                                      JOIN categories ON categories.id= posts.category_id
                                      JOIN users on users.id= posts.user_id where category_id = 8 ORDER by posts.id DESC "));
        $return_arr = array();
        foreach ($moving as $imgs) {
            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_phone = $imgs->user_phonee;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            $arr = explode(",", $imgs->image);
            
            $post_id = $imgs->post_id;
            $post_bid = PostBid::where('post_id', $post_id)->first();
            
            $bid_price= 0;
            if(!empty($post_bid))
            {
                $bid_price = $post_bid->price;
            }
            
            
            
            $bid_status = $imgs->bid_status;
            if($price_type == "سوم")
            {
                
                $bid_expired_days = $imgs->bid_expired_days;
                $string = $bid_expired_days;
                $expired_days = preg_replace('/\D/', '', $string); 
                
                $created_at = $imgs->created_at;
                $current_date = date('Y-m-d H:i:s');
                $created_at_timestamp = strtotime($created_at);
                $created_at_plus_days = date('Y-m-d H:i:s', strtotime('+'.$expired_days.' days', $created_at_timestamp));
                
                $datetime1 = date_create($created_at_plus_days);
                $datetime2 = date_create($current_date);
                $interval = date_diff($datetime1, $datetime2);
                
                $days_difference = $interval->format('%a');
                //  dd($current_date .' > '. $created_at_plus_days );
                 
                
                if($created_at_plus_days == $current_date || $current_date > $created_at_plus_days ){
                // dd('hdkf');
                     $bid_status = 1;
                }
            }
             if($price_type == "حد"){
                 $bid_status = $imgs->bid_status;
             }
            
            
            
            
            
            
            $return_arr[] = array(
                'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail, 'bid_status'=>$bid_status,'user_id' => $user_id,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'bid_price'=>$highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at,
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 
                'name' => $name, 'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }
    //  public function get_camelEquipment(Request $request){

    // $request->validate([
    //     'user_id' => 'required'
    // ]);
    // if($request->user_id==0){
    //     $posts=Post::where('category_id',8)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

    //     $return_arr=array();
    //     foreach($posts as $post){
    //         $category = $post->category;
    //         $user = $post->user;
    //         $images = $post->images;
    //         $liked=false;

    //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
    //         if($check){
    //             $liked=true;

    //         }

    //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

    //     }
    //     return response()->json(['status' => true, 'posts' => $return_arr]);
    // }else{
    //     $followings=UserFollower::where('user_id',$request->user_id)->get();

    //     $following_array=array();
    //     foreach($followings as $following){
    //         $following_array[] = $following->follower_id;
    //     }

    //     $following_array[] = $request->user_id;

    //     $posts=Post::where('category_id',8)->whereIn('user_id',$following_array)->orderBy('id','desc')->where('status',1)->withCount('likes')->withCount('comments')->get();

    //     $return_arr=array();
    //     foreach($posts as $post){
    //         $category = $post->category;
    //         $user = $post->user;
    //         $images = $post->images;

    //         $liked=false;

    //         $check=PostLike::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
    //         if($check){
    //             $liked=true;

    //         }

    //         $return_arr[]=array('post'=>$post,'liked'=>$liked);

    //     }
    //     return response()->json(['status' => true, 'posts' => $return_arr]);
    // }

    public function dashboard(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $followings = UserFollower::where('user_id', $request->user_id)->get();

        if ($followings->count() == 0) {
            $posts = Post::where('status', 1)->withCount('likes')->withCount('comments')->inRandomOrder()->limit(15)->get();

            $return_arr = array();
            foreach ($posts as $post) {
                $category = $post->category;
                $user = $post->user;
                $images = $post->images;

                $bids = $post->bids;
                foreach ($bids as $bid) {
                    $bid->user;
                }
                $bid = $post->getLastBid();

                if ($bid) {

                    $user = $bid->user;
                }

                $liked = false;

                $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $liked = true;
                }

                $return_arr[] = array('post' => $post, 'liked' => $liked, 'last_bid' => $bid);
            }
            return response()->json(['status' => true, 'posts' => $return_arr]);
        } else {

            $followings = UserFollower::where('user_id', $request->user_id)->get();

            $following_array = array();
            foreach ($followings as $following) {
                $following_array[] = $following->follower_id;
            }
            $following_array[] = $request->user_id;

            $posts = Post::where('status', 1)->whereIn('user_id', $following_array)->withCount('likes')->withCount('comments')->inRandomOrder()->limit(15)->get();

            $return_arr = array();
            foreach ($posts as $post) {
                $category = $post->category;
                $user = $post->user;
                $images = $post->images;

                $bids = $post->bids;
                foreach ($bids as $bid) {
                    $bid->user;
                }
                $bid = $post->getLastBid();

                if ($bid) {

                    $user = $bid->user;
                }

                $liked = false;

                $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $liked = true;
                }

                $return_arr[] = array('post' => $post, 'liked' => $liked, 'last_bid' => $bid);
            }
            return response()->json(['status' => true, 'posts' => $return_arr]);
        }
    }

    public function post_like(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'post_id' => 'required',
            'type' => 'required',
        ]);

        if ($request->type == 'normal') {
            $check = PostLike::where('post_id', $request->post_id)->where('user_id', $request->user_id)->first();
            if ($check) {
                if ($check->delete()) {
                    return response()->json(['status' => true, 'message' => 'Successfully Unliked']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Error Occurred']);
                }
            } else {
                $like = new PostLike();
                $like->post_id = $request->post_id;
                $like->user_id = $request->user_id;
                if ($like->save()) {
                    return response()->json(['status' => true, 'message' => 'Successfully liked']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Error Occurred']);
                }
            }
        } elseif ($type == 'moving') {

            $check = PostLike::where('moving_id', $request->post_id)->where('user_id', $request->user_id)->get();
            if ($check) {
                if ($check->delete()) {
                    return response()->json(['status' => true, 'message' => 'Successfully Unliked']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Error Occurred']);
                }
            } else {

                if ($type == 'unlike') {
                    $like = new PostLike();
                    $like->moving_id = $request->post_id;
                    $like->user_id = $request->user_id;

                    if ($like->delete()) {
                        return response()->json(['status' => true, 'message' => 'Successfully unliked']);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Error Occurred']);
                    }
                } else {
                    $like = new PostLike();
                    $like->moving_id = $request->post_id;
                    $like->user_id = $request->user_id;
                    if ($like->save()) {
                        return response()->json(['status' => true, 'message' => 'Successfully liked']);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Error Occurred']);
                    }
                }
            }
        }
    }

    public function comment_like(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'comment_id' => 'required',
        ]);

        $check = CommentLike::where('comment_id', $request->comment_id)->where('user_id', $request->user_id)->first();
        if ($check) {
            if ($check->delete()) {
                $total_like =DB::select(DB::raw("SELECT count(*) as total_comment_likes FROM `comment_likes` WHERE `comment_id` = $request->comment_id"));
                $total_likes = $total_like[0]->total_comment_likes;
                return response()->json(['status' => true, 'message' => 'Successfully Unliked', 'total_likes'=> $total_likes]);
                
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        } else {
            $like = new CommentLike();
            $like->comment_id = $request->comment_id;
            $like->user_id = $request->user_id;
            if ($like->save()) {
                $total_like =DB::select(DB::raw("SELECT count(*) as total_comment_likes FROM `comment_likes` WHERE `comment_id` = $request->comment_id"));
                $total_likes = $total_like[0]->total_comment_likes;
                
                $commented_user_detail= Comment::where('id', $request->comment_id)->first(); 
                $commented_user = $commented_user_detail->user_id;
                $user = User::where('id', $commented_user)->first();
                $comented_user_device_token = $user->device_token;
                
                $login_user = User::where('id', $request->user_id)->first();
                $login_user_name = $login_user->name;
                if($commented_user == $request->user_id)
                {
                   return response()->json(['status' => true, 'message' => 'Successfully liked', 'total_likes'=> $total_likes]);  
                }
                
                if($commented_user != $request->user_id){
                    
                
                $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$comented_user_device_token.'",
                 "notification" : {
                    
                     "body": "'.$login_user_name.' liked your comment"
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    // 'Authorization: key=AAAA_pGO44o:APA91bFhrkEoeEvP9Ukzw5QFnxb5UNPx7DOrrvA5ayJzFY6BsMF0oxkSZt6MveWwSldTiROUMSSsCTyk9ZKE27m2F34pIjuySC_SWR9LuE2G_7Q_Hv4TL7K0Ru77q2qmhAm9bX4DZHgI'
                    // 'Authorization: key=AAAAgBMZwnU:APA91bHSj00NP_xFrGH73gMzaIBCfDtRwYRNgnOjKLqWmOJcvBcUW8KtSw5H4Bv1xDxEskEgCbIxj3TsQ0MqUkQeG9igGc0v6G7B0lsfGeIALY8BZ5KfD7pxIMsjB2tHI5HXb2bYb0XL'
                    'Authorization: key='.$this->firebase_key.''
                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
               
                
                return response()->json(['status' => true, 'message' => 'Successfully liked', 'total_likes'=> $total_likes, 'response'=> $response]);
                    
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        }
    }

    public function view_comment()
    {
        $view = db::select(db::raw(" select comments.* ,users.image as user_images, (select count(*) from comment_likes as cl where cl.comment_id= comments.id) as
                                     total_comments from comments
                                     join users on users.id= comments.user_id
                                     join comment_likes on comment_likes.comment_id= comments.id
                                     order by comments.id desc "));
        return response()->json($view);
    }

    public function get_comment(Request $request)
    {
        $comment_like = '';
        
        if($request->user_id && $request->post_id){
            
            $comment_like = DB::select(DB::raw("SELECT * FROM `comments` 
                                            join comment_likes on comment_likes.comment_id  = comments.id
                                            WHERE `post_id`= $request->post_id and comment_likes.user_id = $request->user_id"));
                                            
            
        }
        
        
          $comment = DB::select(DB::raw("select comments.id,users.image , users.name, comment,comments.created_at, comments.updated_at
                                        from comments
                                        join users on users.id = comments.user_id
                                        where post_id = $request->post_id order by comments.id  desc"));
           
                                      
       
        $return_arr = array();
        $reply_arr = array();
       
        $comments_actual_data = array();
        foreach($comment as $comments){
            $id = $comments->id;
            $image = $comments->image;
            $name = $comments->name;
            $comment = $comments->comment;
            $created_at = $comments->created_at;
            $updated_at = $comments->updated_at;
            $comment_reply = CommentReply::where('comment_id', $comments->id)->get();
            if(!empty($comment_reply))
            {
           
                foreach($comment_reply as $comment_replies)
                {
                    
                    $reply = $comment_replies->reply;
                    $comment_id = $comment_replies->comment_id;
                    $user_id = $comment_replies->user_id;
                    $user_details = User::where('id', $user_id)->first();
                    $user_name = $user_details->name;
                    $user_image = $user_details->image;
                    $reply_arr[]= array('reply'=>$reply, 'comment_id'=>$comment_id,'user_id'=>$user_id, 'name'=>$user_name, 'image'=> $user_image);
                    
                    
                    
                
            }
            }

            
            
            $flagForLike = false;
            if(!empty($comment_like))
            {
                foreach($comment_like as $comment_likes){
                    if ($comment_likes->comment_id === $id) {
                         $flagForLike = true;
              
                }
                }
            }
            
            $total_like = DB::select(DB::raw("SELECT count(*) as total_comment_likes FROM `comment_likes` WHERE `comment_id` = $id"));
            $total_likes =  $total_like[0]->total_comment_likes;
            $return_arr[]= array('id'=> $id, 'name'=> $name, 'image'=>$image, 'comment'=> $comment, 'flagForLike'=> $flagForLike,
            'total_likes'=>$total_likes,'created_at'=>$created_at,'updated_at'=>$updated_at, 'comment_reply'=>$reply_arr);
            
             
           return response()->json($return_arr);
        }    
        
        
        

    }
    public function get_comment_second(Request $request)
    {
        
        
            
        $comments = DB::table('comments')
       ->join('users', 'comments.user_id', '=', 'users.id')
       ->leftJoin('comment_likes', 'comment_likes.comment_id', '=', 'comments.id')
       ->where('comments.post_id', $request->post_id)
       ->select('comments.id as id','name','image','comment','comments.created_at as created_at','comments.updated_at as updated_at', DB::raw('(comment_likes.user_id IS NOT NULL) as flagForLike'),DB::raw('COUNT(comment_likes.id) as total_likes'))
       ->groupBy('comments.id', 'name', 'image', 'comment', 'created_at', 'updated_at')
       ->get();
       
foreach ($comments as $comment) {
    $replies = DB::table('comment_replies')
       ->join('users', 'comment_replies.user_id', '=', 'users.id')
        ->select('reply','comment_id','user_id','name','image')
        ->where('comment_id', $comment->id)
        ->get();
    $comment->comment_reply = $replies;
}

  if($comments){
      return $comments;
  }else{
      return [];
  }
        

    }
    
    
    
    public function get_comments(Request $request)
    {

        $request->validate([
            'post_id' => 'required',
            'type' => 'required',
            'user_id' => 'required',
        ]);

        $return_arr = array();
        if ($request->type == 'normal') {

            $comments = Comment::where('post_id', $request->post_id)->withCount('likes')->get();
            return $comments;
            die;
            foreach ($comments as $comment) {

                $replies = $comment->replies;
                foreach ($replies as $reply) {
                    $user = $reply->user;
                }
                $user = $comment->user;
                $liked = $comment->islike($request->user_id);

                $return_arr[] = array(
                    'comment' => $comment,
                    'liked' => $liked,
                );
            }

            return response()->json(['status' => true, 'comments' => $return_arr]);
        } elseif ($request->type == 'moving') {
        }
    }

    public function add_comment(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'user_id' => 'required',
            'comment' => 'required',
            // 'moving_id' => 'required'
        ]);

       
        $user = User::find($request->user_id);
        $comment = new Comment();
        $comment->user_id = $user->id;
        $comment->user_name = $user->name;
        $comment->date = date('Y-m-d');
        $comment->comment = $request->comment;
        $comment->post_id = $request->post_id;
        $post = Post::find($request->post_id);
        $post->comment_count = (int) $post->comment_count + 1;
        
        $reciver_id=User::find($post->user_id);
        
        
        if ($comment->save() && $post->save()) {
            
       $notification_desc='لديه تعليق على رسالتك ' ;
        DB::table('notifications') ->insert([
            'description' => $notification_desc,
            'sender_id'=> $user->id,
            'receiver_id'=>$reciver_id->id,
            'post_id'=>$post->id,
            'type'=>'comment'
         
            ]);  
            
           
             
          
           
           $user_post =  $post->user_id;
           $user_device_token = User::where('id', $user_post)->first();
        
           $device_token = $user_device_token->device_token;
         
           
           $login_user_data = User::where('id', $request->user_id)->first();
           $login_user = $login_user_data->name;
           $login_user_id = $login_user_data->id;
           
           if($user_post == $login_user_id)
           {
                return response()->json(['status' => true, 'message' => 'تم إضافة التعليق']);
           }
           if($user_post != $login_user_id)
{
            $curl = curl_init();
            curl_setopt_array($curl, array(
                
                CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "to" : "'.$device_token.'",
                    "notification" : {
                        "body": "'.$login_user.' commented your post"
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: key='.$this->firebase_key.''
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
    
    return response()->json(['status' => true, 'message' => 'تم إضافة التعليق', 'response'=> $response]);
}

        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }

      

    }
    
    public function add_competition_comment(Request $request)
    {
        $request->validate([
            'competition_id' => 'required',
            'user_id' => 'required',
            'comment' => 'required',
           
        ]);

       
        $user = User::find($request->user_id);
        $comment = new CompetitionComment();
        $comment->user_id = $user->id;
        $comment->comment = $request->comment;
        $comment->competition_id = $request->competition_id;
        $competition = Competition::find($request->competition_id);
        $competition->comment_count = (int) $competition->comment_count + 1;
       
        if ($comment->save() && $competition->save()) {
            return response()->json(['status' => true, 'message' => 'تم إضافة التعليق']);
        } else {
            return response()->json(['status' => false, 'message' => 'غير قادر على إضافة تعليق']);
        }

      

    }
    
    public function user_profile_old(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        $post_likes_ = '';
        $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        
        $user = User::where('id',$request->user_id)->get();
        if (!empty($user)) {
            
            foreach($user as $users){dd($users);
                
            }
            
            $followers = UserFollower::where('follower_id', $user->id)->get();
            $follower_count = $followers->count();

            $following = UserFollower::where('user_id', $user->id)->get();
            $following_count = $following->count();

            
            $posts = db::select(db::raw("select posts.*,posts.id as post_id,categories.name as category_name, users.image as user_images,users.name as name,
                                        users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status
                                        from posts
                                        join users on users.id = posts.user_id
                                        join categories on categories.id= posts.category_id where posts.status = 1
                                        and posts.user_id = $request->user_id
                                        order by posts.id desc"));
           
            $return_arr = array();
            if (count($posts) > 0) {
               foreach ($posts as $imgs) {
                   $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
                    // $id = $imgs->id;
                    $id = $imgs->post_id;
                    $user_id = $imgs->user_id;
                    $user_name = $imgs->user_name;
                    $user_phone = $imgs->user_phone;
                    $user_email = $imgs->user_email;
                    $chat_status = $imgs->chat_status;
                    $whatsapp_status = $imgs->whatsapp_status;
                    $phone_status = $imgs->phone_status;
                    $category_id = $imgs->category_id;
                    $to_location = $imgs->to_location;
                    $title = $imgs->title;
                    $location = $imgs->location;
                    $color = $imgs->color;
                    $camel_type = $imgs->camel_type;
                    $activity = $imgs->activity;
                    $car_model = $imgs->car_model;
                    $car_type = $imgs->car_type;
                    $price = $imgs->price;
                    $price_type = $imgs->price_type;
                    $date = $imgs->date;
                    $video = $imgs->video;
                    $age = $imgs->age;
                    $description = $imgs->description;
                    $competition_id = $imgs->competition_id;
                    $register = $imgs->register;
                    $account_activity = $imgs->account_activity;
                    $status = $imgs->status;
                    $moving_camel_amount = $imgs->moving_camel_amount;
                    $view_count = $imgs->view_count;
                    $share_count = $imgs->share_count;
                    $like_count = $imgs->like_count;
                    $comment_count = $imgs->comment_count;
                    $created_at = $imgs->created_at;
                    $updated_at = $imgs->updated_at;
                    $commission = $imgs->commission;
                    $category_name = $imgs->category_name;
                    $user_images = $imgs->user_images;
                    $name = $imgs->name;
                    $flagForLike= false;
                    if(!empty($post_likes_)){
                        foreach($post_likes_ as $post_likes_s)
                    {
                        if ($post_likes_s->post_id === $id) {
                         $flagForLike = true;
                      
                        }
                    }
                    }
                    
                    
                    $arr = explode(",", $imgs->image);
                    
                    $bid_price = 0;
                            
                            if($category_id == 2 || $category_id == 6 || $category_id == 8)
                            {
                                $post_id = $imgs->post_id;
                                $post_bid = PostBid::where('post_id', $post_id)->first();
                                if(!empty($post_bid))
                                {
                                    $bid_price = $post_bid->price;
                                }
                            }
                            
                    $return_arr[] = array(
                        'img' => $arr, 'id' => $id, 'user_id' => $user_id, 'user_name' => $user_name, 'user_phone' => $user_phone,
                        'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,
                        'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                        'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                        'bid_price'=> $highest_bid,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                        'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                        'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                        'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
                    );
        }

            }
            
             

            return response()->json([
                // 'status' => true,
                'user' => $user,
                // 'follow_status' => $following_user,
                'follwers' => $follower_count,
                'following' => $following_count,
                // 'offers' => $bids_count,
                // 'shares' => $shares,
                // 'sales_purchase' => $sales_count,
                // 'likes' => $likes_count,
                'posts' => $return_arr,
            ]);
           
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unable to find the user',
            ]);
        }
    }

public function user_profile(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        $post_likes_ = '';
         $post_likes_ = PostLike::where('user_id',$request->user_id)->get();
        $user = User::where('id',$request->user_id)->get();
       
        $users_arr = array();
        if (!empty($user)) {
            
            foreach($user as $users){
                $uid = $users->id;
                $name = $users->name;
                $email = $users->email;
                $email_verified_at = $users->email_verified_at;
                $password = $users->password;
                $phone = $users->phone;
                $image = $users->image;
                $whatsapp_no = $users->whatsapp_no;
                $whatsapp_status = $users->whatsapp_status;
               
                
                
                
                
                if($users->subscription=='normal'){
                $users->subscription='عضو' ;   
                } 
                else if($users->subscription=='vip'){
                $users->subscription='عضو مهم' ;   
                } 
                else if($users->subscription=='famous'){
                $users->subscription='عضو مميز' ;   
                }
                $subscription = $users->subscription;
                
                
                
                
                $status = $users->status;
                $chat_status = $users->chat_status;
                $role = $users->role;
                $token = $users->token;
                $firebaseID = $users->firebaseID;
                $device_type = $users->device_type;
                $shares = $users->shares;
                $location = $users->location;
                $remember_token = $users->remember_token;
                $created_at = $users->created_at;
                $updated_at = $users->updated_at;
                $phone_status = $users->phone_status;
                $device_token = $users->device_token;
                $socialType = $users->socialType;
                $socialToken = $users->socialToken;
                $is_complete = $users->is_complete;
                
                $users_arr[]= array('Uid'=>$uid,'name'=>$name,'email'=>$email,'email_verified_at'=>$email_verified_at,'password'=>$password,
                'phone'=>$phone,'image'=>$image,'whatsapp_no'=>$whatsapp_no, 'whatsapp_status'=>$whatsapp_status,'subscription'=>$subscription,
                'status'=>$status, 'chat_status'=>$chat_status,'role'=>$role, 'token'=>$token, 'firebaseID'=>$firebaseID, 'device_type'=>$device_type,
                'shares'=>$shares, 'location'=>$location,'remember_token'=>$remember_token, 'created_at'=>$created_at,'updated_at'=>$updated_at,
                'phone_status'=>$phone_status,'device_token'=>$device_token, 'socialType'=>$socialType,'socialToken'=>$socialToken, 'is_complete'=>$is_complete);
            }
            
            $followers = UserFollower::where('follower_id', $request->user_id)->get();
            $follower_count = $followers->count();

            $following = UserFollower::where('user_id', $request->user_id)->get();
            $following_count = $following->count();
           

            
            $posts = db::select(db::raw("select posts.*,posts.id as post_id,categories.name as category_name, users.image as user_images,users.name as name
                                        from posts
                                        join users on users.id = posts.user_id
                                        join categories on categories.id= posts.category_id where posts.status = 1
                                        and posts.user_id = $request->user_id
                                        order by posts.id desc"));
           
            $return_arr = array();
            if (count($posts) > 0) {
               foreach ($posts as $imgs) {
                     $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
                    // $id = $imgs->id;
                    $id = $imgs->post_id;
                    $user_id = $imgs->user_id;
                    $user_name = $imgs->user_name;
                    $user_phone = $imgs->user_phone;
                    $user_email = $imgs->user_email;
                    $category_id = $imgs->category_id;
                    $to_location = $imgs->to_location;
                    $title = $imgs->title;
                    $location = $imgs->location;
                    $color = $imgs->color;
                    $camel_type = $imgs->camel_type;
                    $activity = $imgs->activity;
                    $car_model = $imgs->car_model;
                    $car_type = $imgs->car_type;
                    $price = $imgs->price;
                    $price_type = $imgs->price_type;
                    $date = $imgs->date;
                    $video = $imgs->video;
                    $age = $imgs->age;
                    $description = $imgs->description;
                    $competition_id = $imgs->competition_id;
                    $register = $imgs->register;
                    $account_activity = $imgs->account_activity;
                    $status = $imgs->status;
                    $moving_camel_amount = $imgs->moving_camel_amount;
                    $view_count = $imgs->view_count;
                    $share_count = $imgs->share_count;
                    $like_count = $imgs->like_count;
                    $comment_count = $imgs->comment_count;
                    $created_at = $imgs->created_at;
                    $updated_at = $imgs->updated_at;
                    $commission = $imgs->commission;
                    $category_name = $imgs->category_name;
                    $user_images = $imgs->user_images;
                    $name = $imgs->name;
                    $flagForLike= false;
                    if(!empty($post_likes_)){
                        foreach($post_likes_ as $post_likes_s)
                    {
                        if ($post_likes_s->post_id === $id) {
                         $flagForLike = true;
                      
                        }
                    }
                    }
                    
                    
                    $arr = explode(",", $imgs->image);
                    
                    $bid_price = 0;
                            
                            if($category_id == 2 || $category_id == 6 || $category_id == 8)
                            {
                                $post_id = $imgs->post_id;
                                $post_bid = PostBid::where('post_id', $post_id)->first();
                                if(!empty($post_bid))
                                {
                                    $bid_price = $post_bid->price;
                                }
                            }
                            
                    $return_arr[] = array(
                        'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail, 'user_id' => $user_id, 'user_name' => $user_name, 'user_phone' => $user_phone,
                        'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                        'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                        'bid_price'=> $bid_price,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                        'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                        'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                        'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
                    );
        }

            }
            
            




            return response()->json([
                // 'status' => true,
                'user' => $users_arr,
                // 'follow_status' => $following_user,
                'follwers' => $follower_count,
                'following' => $following_count,
                // 'offers' => $bids_count,
                // 'shares' => $shares,
                // 'sales_purchase' => $sales_count,
                // 'likes' => $likes_count,
                'posts' => $return_arr,
            ]);
           
        } else {
            return response()->json([
                'status' => false,
                'message' => 'غير قادر على العثور على المستخدم',
            ]);
        }
    }

    public function add_reply(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'comment_id' => 'required',
            'reply' => 'required',
            'post_id' => 'required',
           
        ]);
        $post = Post::find($request->post_id);
        $post->comment_count = (int) $post->comment_count + 1;
        $post->save();

        // if($request->type=='normal'){
        $user = User::find($request->user_id);
        $reply = new CommentReply();
        $reply->user_id = $user->id;
        $reply->reply = $request->reply;
        $reply->comment_id = $request->comment_id;
       
        if ($reply->save()) {
            
            return response()->json(['status' => true, 'message' => 'تمت إضافة الرد']);
        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }
    }

    public function get_following(Request $request)
    {

        $request->validate([
            'user_id' => 'required',
        ]);

        $return_arr = array();
        $followings = UserFollower::where('user_id', $request->user_id)->get();
        foreach ($followings as $following) {
            $return_arr[] = User::find($following->follower_id);
        }
        return response()->json(['status' => true, 'followings' => $return_arr]);
    }

    public function get_follower(Request $request)
    {

        $request->validate([
            'user_id' => 'required',
        ]);

        $return_arr = array();
        $followings = UserFollower::where('follower_id', $request->user_id)->get();
        foreach ($followings as $following) {
            $return_arr[] = User::find($following->user_id);
        }
        return response()->json(['status' => true, 'followers' => $return_arr]);
    }

    public function delete_post(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            // 'type' => 'required'
        ]);
        // if($request->type=='normal'){
        $post = Post::find($request->post_id);

        foreach ($post->images as $image) {

            Storage::delete('images/posts/' . $image->image);
            $image->delete();
        }
        if ($post->delete()) {
            return response()->json(['status' => true, 'message' => 'تم حذف المشاركة']);
        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }
        // }
        // elseif($request->type=='moving'){
        $moving = Moving::find($request->post_id);

        if ($moving->delete()) {
            return response()->json(['status' => true, 'message' => 'تم حذف المشاركة']);
        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }
        // }
    }

    public function get_users(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);
        $return_arr = array();

        if ($request->user_id == 0) {

            $users = User::where('role', 2)->get();
            foreach ($users as $user) {

                $return_arr[] = array(
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => $user->image,
                    'following' => 0,
                );
            }
        } else {
            $followings = UserFollower::where('user_id', $request->user_id)->get();

            $following_array = array();
            foreach ($followings as $following) {
                $following_array[] = $following->follower_id;
            }

            $users = User::where('id', '!=', $request->user_id)->where('role', 2)->get();
            foreach ($users as $user) {
                if (in_array($user->id, $following_array)) {
                    $following = 1;
                } else {
                    $following = 0;
                }
                $return_arr[] = array(
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => $user->image,
                    'following' => $following,
                );
            }
        }

        $settings = Settings::all()->pluck('value', 'key')->toArray();

        return response()->json(['status' => true, 'users' => $return_arr, 'settings' => $settings]);
    }

    public function add_female_camel(Request $request)
    {
        $request->validate([
            'user_id' => "required",
            // 'images' => 'required',
            'color' => 'required',
            'type' => 'required',
            'location' => 'required',
            'description' => 'required',
            'title' => 'required',
            // "video" => "required"
        ]);

        $user = User::find($request->user_id);

        $post = new Post();
        $post->color = $request->color;
        $post->camel_type = $request->type;
        $post->location = $request->location;
        $post->description = $request->description;
        $post->title = $request->title;
        $post->user_phone = $user->phone;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 11;
        $post->status = 1;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();
        /***************************************
         *    VIDEOS
         * **************************************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
        }

        /************************************************
         *        IMAGES
         ****************************************************/
        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }



        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function get_camel_female(Request $request)
    {
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
        
        $camel = db::select(db::raw("select posts.*,categories.name as category_name, users.image as user_images,users.name as name,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status
        from posts
        join users on users.id = posts.user_id
        join categories on categories.id= posts.category_id where posts.category_id = 11
        order by posts.id desc"));

        $return_arr = array();
        foreach ($camel as $imgs) {
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phone;
            $user_email = $imgs->user_email;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $name = $imgs->name;
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            

            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail, 'user_id' => $user_id, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at,
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images,
                'name' => $name,'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function getcamelMove(Request $request)
    {   
        
            
         $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }

        $moving = db::select(db::raw("Select posts.*,categories.name as category_name,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status, users.chat_status as chat_status,users.image as user_images,users.name as name from posts
                                        join users on users.id = posts.user_id
                                        join categories on categories.id= posts.category_id
                                        where category_id = 5 && posts.status = 1
order by posts.id desc"));

        $return_arr = array();
        foreach ($moving as $imgs) {
            $thumbnail = DB::table('post_thumbnail')
            ->where('post_id', $imgs->id)
            ->select('thumbnail')
            ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phone;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $name = $imgs->name;
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }
            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'user_id' => $user_id,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'thumbnail'=>$thumbnail,'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images,
                'name' => $name, 'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
        // return response()->json($moving);

    }

    public function get_news(Request $request)
    {
        $news_likes='';
        
        $user_id = $request->user_id;
           if($user_id)
            {   
                $news_likes = NewsRating::where(['user_id'=> $user_id])->get(); 
            }
            
        $news = News::where('status', 'active')->orderBy('id', 'desc')->get();
       
        foreach ($news as $new) {
            
            $flagForRating= false;
            
            if(!empty($news_likes))
            {
                foreach($news_likes as $news_likes_s){
                    if($news_likes_s->news_id == $new->id)
                    {
                        $flagForRating = true;
                    }
                }
            }
            
            $user = $new->user;
            $comments = $new->comments;
            
            foreach ($comments as $comment) {
                $user = $comment->user;
               
            }
             $new->flagForRating = $flagForRating;
        }
        return response()->json(['status' => true, 'news' => $news]);
    }

    public function get_news_comments()
    {
        $news_comments = db::select(db::raw("SELECT news_id, comment, users.name, users.image FROM `news_comments`
        join users on users.id= news_comments.user_id
        order by news_id desc"));
        return response()->json($news_comments);
    }

    public function add_newscomment(Request $request)
    {
        $request->validate([
            'news_id' => 'required',
            'user_id' => 'required',
            'comment' => 'required',
        ]);

        $user = User::find($request->user_id);
        $comment = new NewsComment();
        $comment->user_id = $user->id;
        $comment->comment = $request->comment;
        $comment->news_id = $request->news_id;
        if ($comment->save()) {
            return response()->json(['status' => true, 'message' => 'تم إضافة التعليق']);
        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }
    }

    public function add_bid(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "post_id" => "required",
            "price" => "required",
        ]);
        $check = PostBid::where('user_id', $request->user_id)->where('post_id', $request->post)->first();
        $checkaward = Post::where('id', $request->post_id)->where('bid_status', 1)->first();

        if ($check) {
            return response()->json(['status' => false, 'message' => 'بالفعل المزايدة على هذا المنصب']);
        } elseif ($checkaward) {
            return response()->json(['status' => true, 'message' => 'منحت بالفعل']);
        } else {
            $bid = new PostBid();
            $bid->user_id = $request->user_id;
            $bid->post_id = $request->post_id;
            $bid->price = $request->price;
            if ($bid->save()) {
              $post_check = Post::where('id', $request->post_id)->where('bid_status', 0)->first(); 
              $notification_desc='لديه عرض على رسالتك ' ;
                DB::table('notifications') ->insert([
                'description' => $notification_desc,
                'sender_id'=> $request->user_id,
                'receiver_id'=>$post_check->user_id,
                'post_id'=>$post_check->id,
                'type'=>'bid'
                
                ]);  
                
           $user_post =  $post_check->user_id;
           $user_device_token = User::where('id', $user_post)->first();
           $device_token = $user_device_token->device_token;
           $login_user_data = User::where('id', $request->user_id)->first();
           $login_user = $login_user_data->name;
           $login_user_id = $login_user_data->id;
                
                
                 if($user_post != $login_user_id)
           {
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>'{
             "to" : "'.$device_token.'",
             "notification" : {
                
                 "body": "'.$login_user.' لديه عرض على رسالتك"
             }
            }',
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                // 'Authorization: key=AAAA_pGO44o:APA91bFhrkEoeEvP9Ukzw5QFnxb5UNPx7DOrrvA5ayJzFY6BsMF0oxkSZt6MveWwSldTiROUMSSsCTyk9ZKE27m2F34pIjuySC_SWR9LuE2G_7Q_Hv4TL7K0Ru77q2qmhAm9bX4DZHgI'
                'Authorization: key='.$this->firebase_key.''
              ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return response()->json(['status' => true, 'message' => 'وأضاف العطاء']);
            } else {
                return response()->json(['status' => false, 'message' => 'حدث خطأ']);
            }
        }
    }
    }

    public function get_bids(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);
        
        $bids = PostBid::where('user_id', $request->user_id)->orderBy('id', 'desc')->get();

        $postss = DB::select(DB::raw("select posts.*,post_bids.*,posts.id as post_id from post_bids
                                      left join posts on posts.id = post_bids.post_id
                                      where post_bids.user_id = $request->user_id order by posts.id DESC"));
       

            foreach ($bids as $bid) {
                   
                    $user = $bid->user;
                    $return_arr = array();
                    $posts = Post::select('posts.*', 'users.image as user_image')
                    ->leftJoin('users', 'users.id', '=', 'posts.user_id')
                    ->where('posts.id', $bid->post->id)
                    ->get();
                    
                    foreach($posts as $imgs){
                            $highest_bid = DB::table('post_bids')->where('post_id', $imgs->id)->max('price');
                            $thumbnail = DB::table('post_thumbnail')
                            ->where('post_id', $imgs->id)
                            ->select('thumbnail')
                            ->first();
                            
                            Post::where('id', $imgs->id)->update(['bid_price' => $highest_bid]);
                            $id = $imgs->id;
                            $user_id = $imgs->user_id;
                            $user_name = $imgs->user_name;
                            $user_phone = $imgs->user_phone;
                            $user_image = $imgs->user_image;
                            $user_email = $imgs->user_email;
                            $category_id = $imgs->category_id;
                            $to_location = $imgs->to_location;
                            $title = $imgs->title;
                            $location = $imgs->location;
                            $color = $imgs->color;
                            $camel_type = $imgs->camel_type;
                            $activity = $imgs->activity;
                            $car_model = $imgs->car_model;
                            $car_type = $imgs->car_type;
                            $price = $imgs->price;
                            $bid_price = $highest_bid;
                            $price_type = $imgs->price_type;
                            $date = $imgs->date;
                            $video = $imgs->video;
                            $age = $imgs->age;
                            $description = $imgs->description;
                            $competition_id = $imgs->competition_id;
                            $register = $imgs->register;
                            $account_activity = $imgs->account_activity;
                            $status = $imgs->status;
                            $moving_camel_amount = $imgs->moving_camel_amount;
                            $view_count = $imgs->view_count;
                            $share_count = $imgs->share_count;
                            $like_count = $imgs->like_count;
                            $comment_count = $imgs->comment_count;
                            $created_at = $imgs->created_at;
                            $updated_at = $imgs->updated_at;
                            $commission = $imgs->commission;
                            $bid_price = $highest_bid;
                            $arr = explode(",", $imgs->image);
                            $bid_status = $imgs->bid_status;
                            $return_arr[] = array(
                                'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail,'user_id' => $user_id,'user_images'=>$user_image, 'name' => $user_name, 'user_phone' => $user_phone,
                                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,'bid_price'=> $bid_price
                                ,'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                                'commission' => $commission
                            );
                    }
            // $posts = $bid->post->id;
            // $image = $posts->img;
           
            $bid->posts = $return_arr;
             
           
        }
           
    
        return response()->json(['status' => true, 'bids'=>$bids]);
    }

    public function sell(Request $request)
    {
        $request->validate([
            "seller_id" => "required",
            "purchaser_id" => "required",
            "post_id" => "required",
            "price" => "required",
        ]);

        $seller = User::find($request->seller_id);
        $purchaser = User::find($request->purchaser_id);
        $post = Post::find($request->post_id);

        $sale = new Sale();
        $sale->seller_id = $seller->id;
        $sale->purchaser_id = $purchaser->id;
        $sale->post_id = $post->id;
        $sale->seller_name = $seller->name;
        $sale->seller_phone = $seller->phone;
        $sale->purchaser_name = $purchaser->name;
        $sale->purchaser_phone = $purchaser->phone;
        $sale->date = date('Y-m-d');
        $sale->category_id = 2;
        $sale->amount = $request->price;
        $sale->percentage = 10;
        $sale->starting_bid = $post->price;

        if ($sale->save()) {
            $post->status = 0;
            $post->save();

            $bids = PostBid::where('post_id', $request->post_id)->orderBy('id', 'desc')->get();
            foreach ($bids as $bid) {

                $title = "Camel Sold";
                $message = "Post on which you added bid has been sold";
                $user = User::find($bid->user_id);

                $this->sendPushToUser($user, $title, $message);

                $notification = new Notification();
                $notification->sender_id = $seller->id;
                $notification->receiver_id = $bid->user_id;
                $notification->description = $message;
                $notification->type = "sell";
                $notification->post_id = $post->id;
                $notification->save();
            }

            return response()->json(['status' => true, 'message' => 'Success']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error']);
        }
    }

    public function get_sales(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);

        $sales = Sale::where('seller_id', $request->user_id)->get();
        foreach ($sales as $sale) {
            $purchaser = $sale->purchaser;
            $category = $sale->category;
            $post = $sale->post;
        }

        return response()->json(['status' => true, 'sales' => $sales]);
    }

    public function get_purchases(Request $request)
    {

        $request->validate([
            "user_id" => "required",
        ]);

        $purchases = Sale::where('purchaser_id', $request->user_id)->get();
        foreach ($purchases as $purchase) {
            $seller = $purchase->seller;
            $category = $purchase->category;
            $post = $purchase->post;
        }

        return response()->json(['status' => true, 'purchases' => $purchases]);
    }

    public function update_image(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "image" => "required",
        ]);

        $user = User::find($request->user_id);

        Storage::delete('images/profiles/' . $user->image);

        $image = preg_replace('/^data:image\/\w+;base64,/', '', $request->image);
        // $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $type = explode(';', $image)[0];
        $type = explode('/', $type)[1];
        $imageName = "profile" . md5(rand(100, 1000)) . rand(100, 1000) . ".jpeg";
        Storage::disk('public')->put('/images/profiles/' . $imageName, base64_decode($image));

        $user->image = $imageName;
        if ($user->save()) {
            return response()->json(['status' => true, 'message' => 'Image Updated']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error']);
        }
    }

    public function verify_phone(Request $request)
    {
        $request->validate([
            "phone" => "required",
        ]);

        $user = User::where('phone', $request->phone)->first();
        if ($user) {

            return response()->json(['status' => true, 'message' => 'Record Found']);
        } else {
            return response()->json(['status' => false, 'message' => 'No Record Found of this Number']);
        }
    }

    public function passwod_reset(Request $request)
    {
        $request->validate([
            "phone" => "required",
            "password" => "required",
        ]);

        $user = User::where('phone', $request->phone)->first();

        
        if ($user) {
            $user->password = Hash::make($request->password);
            if ($user->save()) {
                return response()->json(['status' => true, 'message' => 'تمت إعادة تعيين كلمة المرور بنجاح']);
            } else {
                return response()->json(['status' => false, 'message' => 'حدث خطأ']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'لم يتم العثور على مستخدم لهذا الرقم']);
        }
    }



    public function add_notification(Request $request)
    {
        $request->validate([
            "sender_id" => "required",
            "receiver_id" => "required",
            "description" => "required",
            "type" => "required",
        ]);

        $notification = new Notification();
        $notification->sender_id = $request->sender_id;
        $notification->receiver_id = $request->receiver_id;
        $notification->description = $request->description;
        $notification->type = $request->type;
        if ($request->has('post_id')) {
            $notification->post_id = $request->post_id;
        }
        if ($notification->save()) {
            return response()->json(['status' => true, 'message' => 'Success']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
    }

    public function get_notification(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);

        $notifications = Notification::where('receiver_id', $request->user_id)->orderBy('id', 'desc')->get();
        foreach ($notifications as $notification) {
            $sender = $notification->sender;
        }


        return response()->json(['status' => true, 'senders' => $notifications]);
    }
    public function single_post(Request $request)
    {
        $request->validate([
            "post_id" => "required",
            "user_id" => "required",
        ]);

        $post = Post::where('id', $request->post_id)->withCount('likes')->withCount('comments')->first();

        $category = $post->category;
        $user = $post->user;
        $images = $post->images;

        $bids = $post->bids;
        foreach ($bids as $bid) {
            $bid->user;
        }
        $bid = $post->getLastBid();

        $liked = false;

        $check = PostLike::where('post_id', $post->id)->where('user_id', $request->user_id)->first();
        if ($check) {
            $liked = true;
        }

        if ($bid) {

            $user = $bid->user;
        }
        return response()->json(['status' => true, 'post' => $post, 'last_bid' => $bid, 'liked' => $liked]);
    }

    public function add_rating(Request $request)
    {
        $request->validate([
            "rating" => "required",
            "news_id" => "required",
            "user_id" => 'required'
        ]);
        
        $check = NewsRating::where(['user_id'=> $request->user_id, 'news_id'=> $request->news_id])->first();
        if(!empty($check))
        {
            return response()->json(['status' => true, 'message' => 'Already exists']);
        }else
        {
             $rating = new NewsRating();
             $rating->user_id = $request->user_id;
             $rating->news_id = $request->news_id;
             
             $news = News::find($request->news_id);
             $news->rating = (int) $news->rating + (int) $request->rating;
             $news->rating_count = (int) $news->rating_count + 1;
        
        if ($news->save() && $rating->save()) {
            
            return response()->json(['status' => true, 'message' => 'Rating Added']);
            
        } else {
            
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
            
        }
        }
       
    }

    public function get_rating(Request $request)
    {
        $request->validate([
            "news_id" => "required",
        ]);

        $news = News::find($request->news_id);
        if ($news->rating_count == 0) {
            $rating = 0;
        } else {
            $rating = (int) $news->rating;
            $rating_count = (int) $news->rating_count;

            $rating = $rating / $rating_count;
        }

        if ($news->save()) {
            return response()->json(['status' => true, 'rating' => $rating]);
        } else {
            return response()->json(['status' => false, 'message' => 'حدث خطأ']);
        }
    }

    public function delete_notification(Request $request)
    {
        $request->validate([
            "notification_id" => "required",
        ]);

        $notification = Notification::find($request->notification_id);

        if ($notification->delete()) {
            return response()->json(['status' => true, 'message' => 'Deleted']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
    }

    public function sendPushToUser($user, $title, $push_message)
    {
        try {
            if ($user->device_type == 'ios') {
                $this->sendPushToIOS($user->token, $title, $push_message, "user");
                //return \PushNotification::app('IOSUser') ->to($user->device_token) ->send($push_message);

            } elseif ($user->device_type == 'android') {

                //return \PushNotification::app('AndroidUser') ->to($user->device_token)->send($push_message);
                $this->sendPushToAndroid($user->token, $title, $push_message, "user");
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function sendPushToAndroid($token, $title, $push_message, $deviceOf)
    {

        try {

            $notification['body'] = $push_message;
            $notification['title'] = $title;
            $notification['sound'] = "default";
            $notification['color'] = "#203E78";

            $url = 'https://fcm.googleapis.com/fcm/send';
            $fields = array(
                'to' => $token,
                'notification' => $notification,
            );

            $headers = array(
                // 'Authorization:key =AAAA3FEKYpQ:APA91bGZNxYS2WJtArkkUFIjbuigSsK6NsqdETn6mWTG7yY3bfPOAAgvCMtbYINQHI-bvGMvxAN_y5mwCbqkbI3jxMxA1G0EIye1Z1F1gMJZM5NlHgvw8Fs3j-HNwutcO8EcJSVvEqEy',
                'Authorization: key='.$this->firebase_key.'',
                'Content-Type: application/json',
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);

            if ($result === false || $result === null || !$result) {
                die('Curl failed: ' . curl_error($ch));
            }
            curl_close($ch);
            if ($result) {
                return response()->json(['status' => '200', 'message' => trans('Notification sent successfully'), 'notification' => $notification]);
            } else {
                return response()->json(['status' => '404', 'error' => trans('Notification Could Not be sent')], 500);
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * Sending Push to a IOS device of user and provider.
     *
     * @return void
     */
    public function sendPushToIOS($Token, $Title, $push_message, $deviceOf)
    {

        try {

            $url = "https://fcm.googleapis.com/fcm/send";
            $token = $Token;
                    

            // $serverKey = 'AAAA3FEKYpQ:APA91bGZNxYS2WJtArkkUFIjbuigSsK6NsqdETn6mWTG7yY3bfPOAAgvCMtbYINQHI-bvGMvxAN_y5mwCbqkbI3jxMxA1G0EIye1Z1F1gMJZM5NlHgvw8Fs3j-HNwutcO8EcJSVvEqEy';
            $serverKey=$this->firebase_key;
            $title = $Title;
            $body = $push_message;
            $notification = array('title' => $title, 'text' => $body, 'sound' => 'default', 'badge' => '1');
            $arrayToSend = array('to' => $token, 'notification' => $notification, 'priority' => 'high');
            $json = json_encode($arrayToSend);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: key=' . $serverKey;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt(
                $ch,
                CURLOPT_CUSTOMREQUEST,

                "POST"
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //Send the request
            $result = curl_exec($ch);
            //Close request
            curl_close($ch);

            if ($result === false) {
                return response()->json(['status' => '404', 'error' => trans('Notification Could Not be sent')], 500);
            } else {
                return response()->json(['status' => '200', 'message' => trans('Notification sent successfully'), 'notification' => $notification]);
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function delete_all_notification(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);
        try {

            $notifications = Notification::where('receiver_id', $request->user_id)->get();
            foreach ($notifications as $notification) {
                $notification->delete();
            }
            return response()->json(['status' => true, 'message' => 'Deleted']);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'message' => $e->getMessage()]);
        }
    }

    public function add_view(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'user_id' => 'required',
        ]);
       
        
        $post_view = ViewPostHistory::where(['user_id'=> $request->user_id, 'post_id'=> $request->post_id])->first();
        if($post_view == null)
        {
             $post = Post::find($request->post_id);
             $post->view_count = (int) $post->view_count + 1;
             $ViewPost = new ViewPostHistory();
             $ViewPost->post_id = $request->post_id;
             $ViewPost->user_id = $request->user_id;
             if($ViewPost->save() && $post->save())
             {
                  return response()->json(['status' => true, 'message' => 'عرض المضافة']);
             }
        }else
        {
            return response()->json(['status' => true, 'message' => 'Already Viewed']);
        }
       
        
        // if ($request->type == "normal") {
        //     $post = Post::find($request->post_id);
        //     $post->view_count = (int) $post->view_count + 1;
        //     if ($post->save()) {
        //         return response()->json(['status' => true, 'message' => 'View Added']);
        //     } else {
        //         return response()->json(['status' => false, 'message' => 'Error Occurred']);
        //     }
        // } elseif ($request->type = "moving") {

        //     $post = Moving::find($request->post_id);
        //     $post->view_count = (int) $post->view_count + 1;
        //     if ($post->save()) {
        //         return response()->json(['status' => true, 'message' => 'View Added']);
        //     } else {
        //         return response()->json(['status' => false, 'message' => 'Error Occurred']);
        //     }
        // }
    }

    public function add_share(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'user_id' => 'required',
        ]);

        // if ($request->type == "normal") {
        $user = User::find($request->user_id);

        $posts = Post::find($request->post_id);
        $posts->share_count = (int) $posts->share_count + 1;
        $post = new Post();

        $post->title = $posts->title;
        $post->location = $posts->location;
        $post->color = $posts->color;
        $post->description = $posts->description;
        $post->user_phone = $posts->user_phone;
        $post->camel_type = $posts->camel_type;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_id = $user->id;
        $post->date = $posts->date;
        $post->category_id = $posts->category_id;
        $post->like_count = '';
        $post->view_count = '';
        $post->comment_count = '';
        $post->to_location = $posts->to_location;
        $post->camel_type = $posts->camel_type;
        $post->activity = $posts->activity;
        $post->car_model = $posts->car_model;
        $post->car_type = $posts->car_type;
        $post->price = $posts->price;
        $post->price_type = $posts->price_type;
        $post->image = $posts->image;
        $post->video = $posts->video;
        $post->age = $posts->age;
        $post->competition_id = $posts->competition_id;
        $post->register = $posts->register;
        $post->account_activity = $posts->account_activity;
        $post->status = $posts->status;
        $post->moving_camel_amount = $posts->moving_camel_amount;

        $share = new Share();
        $share->post_id = $request->post_id;
        $share->user_id = $request->user_id;
        if ($post->save() && $share->save() && $posts->save()) {
            return response()->json(['status' => true, 'message' => 'Share Added']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
        // } elseif ($request->type = "moving") {

        //     $post = Moving::find($request->post_id);
        //     $post->share_count = (int)$post->share_count + 1;
        //     $share = new Share();
        //     $share->post_id = $request->post_id;
        //     $share->user_id = $request->user_id;
        //     if ($post->save() && $share->save()) {
        //         return response()->json(['status' => true, 'message' => 'Share Added']);
        //     } else {
        //         return response()->json(['status' => false, 'message' => 'Error Occurred']);
        //     }
        // }
    }

    public function get_followers(Request $request)
    {
        $followers = UserFollower::select('u.*')
            ->join('users as u', 'u.id', 'user_followers.follower_id')->where('user_followers.user_id', $request->user_id)->get();

        if ($followers->count() > 0) {
            return response()->json(['status' => true, 'message' => 'قائمة المتابعين', 'data' => $followers]);
        } else {
            return response()->json(['status' => false, 'message' => 'لم يتم العثور على أي متابع']);
        }
    }
    public function get_survey($id)
    {
        $survey = db::select(db::raw("select * from survey_details where survey_id = $id"));
        return response()->json($survey);
    }

    public function add_marketing(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            // 'images' => 'required',
            'price' => 'required',
            // 'type' => 'required',
            'location' => 'required',
            'description' => 'required',
            'title' => 'required',
            // "video" => "required"
        ]);

        $user = User::find($request->user_id);
        $comment = new post();
        $comment->price = $request->price;
        $comment->user_id = $user->id;
        $comment->user_name = $user->name;
        $comment->user_phone = $user->phone;
        $comment->user_email = !is_null($user->email) ? $user->email : '';
        $comment->location = $request->location;
        $comment->description = $request->description;
        $comment->title = $request->title;
        $comment->category_id = 9;
        $comment->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $comment->id;
        $notification->save();

        /**************************************************
         *                 VIDEOS
         * ***********************************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $comment->id;
            $post_video->video = $videoName;
            $comment->video = $videoName;
            $post_video->save();
            $comment->save();
        }
        }
        /**************************************************
         *                 IMAGES
         * ***********************************************/

        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $comment->id;
                $image->image = implode(',', $imagee);
                $comment->image = implode(',', $imagee);

                $comment->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));
            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $comment->id;
            $postThumbnail->save();

          
        }
        }


        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function get_marketing(Request $request)
    {   
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }

        $marketing = db::select(db::raw("Select posts.*,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.name as name, users.image as  user_image, categories.name as category_name from posts
          JOIN categories ON categories.id= posts.category_id
          JOIN users on users.id= posts.user_id
          where category_id = 9 ORDER by posts.id DESC "));

        $return_arr = array();
        foreach ($marketing as $imgs) {
                $thumbnail = DB::table('post_thumbnail')
                ->where('post_id', $imgs->id)
                ->select('thumbnail')
                ->first();
            $id = $imgs->id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phone;
            $user_email = $imgs->user_email;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;

            $category_name = $imgs->category_name;
            $user_images = $imgs->user_image;
            $name = $imgs->name;
            $flagForLike= false;
          
            if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }

            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'thumbnail'=>$thumbnail,'user_id' => $user_id, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count,
                'created_at' => $created_at, 'updated_at' => $updated_at, 'commission' => $commission, 
                'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name, 'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function post_likes(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'post_id' => 'required',
        ]);

        $check = PostLike::where('post_id', $request->post_id)->where('user_id', $request->user_id)->first();
        $posts = Post::where('id', $request->post_id);

        if ($check && $posts) {

            $post = Post::find($request->post_id);
            $post->like_count = (int) $post->like_count - 1;
            if($post->update() && $check->delete())
            {   
                 $post_like = Post::where('id',$request->post_id)->first();
                 $total_likes = $post_like->like_count;
                 return response()->json(['status' => true, 'message' => 'Successfully Unliked','total_likes'=>$total_likes]);
            }
            
           
            // } else {
            //     return response()->json(['status' => false, 'message' => 'Error Occurred']);
            // }

        } else {
            $like = new PostLike();
            $like->post_id = $request->post_id;
            $like->user_id = $request->user_id;
            $like->flagForLike = 'true';
            $post = Post::find($request->post_id);
            $post->like_count = (int) $post->like_count + 1;
            if ($like->save() && $post->save()) {
                $post_like = Post::where('id',$request->post_id)->first();
                $total_likes = $post_like->like_count;
                
                $user_data = $post_like->user_id;
                $post_user_data = User::where('id', $user_data)->first();
                $device_token = $post_user_data->device_token;
                
                $login_user = $request->user_id;
                $login_user_data = User::where('id', $login_user)->first();
                $login_user_name= $login_user_data->name;
                
                
                 $notification_desc='لديه مثل رسالتك ' ;
                DB::table('notifications') ->insert([
                'description' => $notification_desc,
                'sender_id'=> $request->user_id,
                'receiver_id'=>$post->user_id,
                'post_id'=>$post->id,
                'type'=>'like'
                
                ]); 
                
                
                if($login_user == $user_data)
                {
                     return response()->json(['status' => true, 'message' => 'Successfully liked','total_likes'=>$total_likes]); 
                }
                
                if($login_user != $user_data){
                    
                
                $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$device_token.'",
                 "notification" : {
                     
                     "body": "'.$login_user_name.' has like your post",
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    // 'Authorization: key=AAAAgBMZwnU:APA91bHSj00NP_xFrGH73gMzaIBCfDtRwYRNgnOjKLqWmOJcvBcUW8KtSw5H4Bv1xDxEskEgCbIxj3TsQ0MqUkQeG9igGc0v6G7B0lsfGeIALY8BZ5KfD7pxIMsjB2tHI5HXb2bYb0XL'
                    'Authorization: key='.$this->firebase_key.''                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                

                
                
                return response()->json(['status' => true, 'message' => 'Successfully liked','total_likes'=>$total_likes, 'response'=>$response]);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        }
    }
    
    public function competition_likes(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'competition_id' => 'required',
        ]);

        $check = CompetitionLike::where('competition_id', $request->competition_id)->where('user_id', $request->user_id)->first();
        $competitions = Competition::where('id', $request->competition_id)->first();

        if ($check && $competitions) {

            $competition = Competition::find($request->competition_id);
            $competition->like_count = (int) $competition->like_count - 1;
            if($competition->update() && $check->delete())
            {   
                 $competition_like = Competition::where('id',$request->competition_id)->first();
                 $total_likes = $competition_like->like_count;
                 return response()->json(['status' => true, 'message' => 'Successfully Unliked','total_likes'=>$total_likes]);
            }
            
          } else {
            $like = new CompetitionLike();
            $like->competition_id = $request->competition_id;
            $like->user_id = $request->user_id;
            // $like->flagForLike = 'true';
            $competitions = Competition::find($request->competition_id);
            $competitions->like_count = (int) $competitions->like_count + 1;
            if ($like->save() && $competitions->save()) {
                $competitions_like = Competition::where('id',$request->competition_id)->first();
                $total_likes = $competitions_like->like_count;
                return response()->json(['status' => true, 'message' => 'Successfully liked','total_likes'=>$total_likes]);
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        }
    }

    public function get_surveys(Request $request)
    {
        // $request->validate([
            
        //     'user_id' => 'required'
        //     ]);
     

       
        $survey = db::select(db::raw("select * from surveys order by id DESC"));
       
        
        $dataSet = array();

        foreach ($survey as $surveys) {
      
            $survey_detail = Db::select(Db::raw("SELECT * FROM `survey_details`  where survey_id= $surveys->id order by id DESC")); 
            $data = array();

            foreach ($survey_detail as $surveys_details) {

                if ($surveys->id == $surveys_details->survey_id ) {
                    $surveySubmit=false;
                    
                      

                    $survey_submit = SurveySubmit::where(['survey_id'=>$surveys->id , 'survey_detail_id'=>$surveys_details->id])->get();
                    $total_count= count($survey_submit);
                    
                    
                    $question =$surveys_details->question;
                    $survey_detail_id = $surveys_details->id;
                    $image =$surveys_details->image;
                    $correct_answer =$surveys_details->correct_answer;
                    $survey_id =$surveys_details->survey_id;
                    $answer_data=array();
                    $answer = explode(',',$surveys_details->added_answer);
                    $answer_count= array();
                    $total_count_= array();
                    
                    
                    $dataa3 = array();
                    foreach($answer as $answers)
                    {   
                        $survey_submit_answer = SurveySubmit::where(['survey_id'=>$surveys->id , 'survey_detail_id'=>$surveys_details->id, 'answer'=>$answers])->get();
                        
                        $answer_counts = count($survey_submit_answer);
                        
                    if($request->user_id)
                    {
                        
                     $surveySubmit = SurveySubmit::where([
                    'survey_id' => $surveys->id,
                    'survey_detail_id' => $surveys_details->id,
                    'answer'=> $answers,
                    'user_id' => $request->input('user_id') // Assuming you have a user_id in the request
                ])->exists();  // Check if user has submitted this survey
                   
                    }
                        $dataa =['answer'=>$answers, 'answer_count'=>$answer_counts,'flagForUser'=>$surveySubmit];
                        $dataa3[]= $dataa;
                    }
                     $answer_count= $dataa3;
                     
                    
                    $data[]=array('answer'=>$answer_count,'total_count'=>$total_count,'survey_detail_id'=>$survey_detail_id,'question'=>$question,'image'=>$image,'correct_answer'=>$correct_answer,'survey_id'=>$survey_id);
                }
            }
            

            $dataSett = ['title'=>$surveys->title,'end_date'=>$surveys->end_date,'survey_end_status'=>$surveys->status,'created_at'=>$surveys->created_at,'updated_at'=>$surveys->updated_at,'survey_details' => $data];
            array_push($dataSet, $dataSett);
        }
       
        return response()->json(['survey' => $dataSet]);
        
    }

    public function add_survey_old(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'survey_detail_id' => 'required',
            'answer' => 'required',
        ]);
        $check = db::select(db::raw("SELECT * FROM `survey_submits` WHERE `user_id` = $request->user_id and `survey_detail_id` = $request->survey_detail_id"));
       
        if ($check) {
            return response()->json(['status' => 'You have already submit this survey']);
        } else {

            $survey_detail = surveyDetail::find($request->survey_detail_id);
            $user = user::find($request->user_id);
            $survey_submit = new SurveySubmit();
            $survey_submit->user_id = $user->id;
            $survey_submit->survey_detail_id = $survey_detail->survey_id;
            $survey_submit->answer = $request->answer;

            if ($survey_submit->save()) {
                return ['success' => $survey_submit];
            } else {
                return ['error' => "ERROR............"];
            }
        }
    }
    
    public function add_survey(Request $request)
    { 
        
        try {
            $data = $request->json()->get('data');

            if (!isset($data['user_id']) || !isset($data['survey_answers']) || !is_array($data['survey_answers'])) {
                return response()->json(['message' => 'Invalid data format'], 400);
            }

            $user_id = $data['user_id'];

            

            foreach ($data['survey_answers'] as $answerData) {
                if (!isset($answerData['survey_detail_id']) || !isset($answerData['survey_id']) || !isset($answerData['answer'])) {
                    return response()->json(['message' => 'Invalid data format'], 400);
                }
                
                $survey_id = $answerData['survey_id'];
                $survey_end_date = Survey::where('id',$survey_id)->first();
                $end_date = $survey_end_date->end_date; 
                $current_date = date('Y-m-d');
                if($end_date > $current_date || $current_date == $end_date)
                {
                    // Create survey answers
                        $surveyAnswer = new SurveySubmit();
                        $surveyAnswer->survey_detail_id = $answerData['survey_detail_id'];
                        $surveyAnswer->survey_id = $answerData['survey_id'];
                        $surveyAnswer->answer = $answerData['answer'];
                        $surveyAnswer->user_id = $user_id;
                        $surveyAnswer->save();
                }else{
                    return response()->json(['message' => 'Survey is expired!']);
                }
                
            }

            return response()->json(['message' => 'Surveys added successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error adding surveys: ' . $e->getMessage()], 500);
        }
        
    }
    

    public function add_camelMoving(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "location" => "required",
            "description" => "required",
            // "images" => "required",
            "register" => "required",
            "account_activity" => "required",
            "car_model" => "required",
            "car_type" => "required",
            "price" => "required",
            "to_location" => "required",
            // "video" => "required"
        ]);

        $user = User::find($request->user_id);
        $post = new Post();
        $post->title = $request->title;

        $post->location = $request->location;
        $post->description = $request->description;
        $post->user_phone = $user->phone;
        $post->to_location = $request->to_location;
        $post->account_activity = $request->account_activity;
        $post->car_type = $request->car_type;
        $post->register = $request->register;

        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_name = $user->name;
        $post->user_email = !is_null($user->email) ? $user->email : '';
        $post->user_id = $user->id;
        $post->date = date('Y-m-d');
        $post->category_id = 5;
        $post->car_model = $request->car_model;
        $post->price = $request->price;
        $post->status = 0;
        $post->save();

        $notification = new Notification();
        $notification->description = "تمت إضافة رسالتك بنجاح";
        $notification->sender_id = $user->id;
        $notification->post_id = $post->id;
        $notification->save();

        /******************************************
        Videos
         * ****************************************/
        if($request->video && $request->video!='null'){
        if ($image_64 = $request->video) {
            // $path = $request->file('video')->store('videos');
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $video = str_replace($replace, '', $image_64);

            $video = str_replace(' ', '+', $video);

            $videoName = Str::random(10) . '.' . $extension;

            Storage::disk('public')->put('videos/' . $videoName, base64_decode($video));

            $post_video = new PostVideo();
            $post_video->post_id = $post->id;
            $post_video->video = $videoName;
            $post->video = $videoName;
            $post_video->save();
            $post->save();
        }
        }
        /******************************************
Images
         * ****************************************/

        $imagee = array();
        if ($file = $request->images) {

            foreach ($file as $image_64) {

                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png .pdf

                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

                $image = str_replace($replace, '', $image_64);

                $image = str_replace(' ', '+', $image);

                $imageName = Str::random(10) . '.' . $extension;

                Storage::disk('public')->put('/images/posts/' . $imageName, base64_decode($image));
                // $imageName= $imageName.$newname.",";
                $imagee[] = $imageName;

                $image = new PostImage();
                $image->post_id = $post->id;
                $image->image = implode(',', $imagee);
                $post->image = implode(',', $imagee);

                $post->save();
                $image->save();
            }
        }
        if($request->video && $request->video!='null'){
         if ($request->has('thumbnail')) {
            // Decode the JSON data from the thumbnail field
            $thumbnailData = json_decode($request->input('thumbnail'));

            // Extract information from the decoded JSON data
            $path = $thumbnailData->path;
            $mime = $thumbnailData->mime;
            $size = $thumbnailData->size;

            // Extract the image extension from the mime type
            $extension = explode('/', $mime)[1];

            // Generate a unique image name
            $imageName = Str::random(3) . '-' . time() . '.' . $extension;

            // Store the image in the public disk under 'images/thumbnail'
            Storage::disk('public')->put('images/thumbnail/' . $imageName, base64_decode($path));

            // Save additional information in the database
            $postThumbnail = new PostThumbnail;
            $postThumbnail->thumbnail = $imageName;
            $postThumbnail->post_id = $post->id;
          
            $postThumbnail->save();

          
        }
        }



        return response()->json(['status' => true, 'message' => 'تمت إضافة المشاركة']);
    }

    public function send_msg(Request $request)
    {
        $request->validate([

            'sender_id' => 'required',
            'reciever_id' => 'required',
            'message' => 'required',

        ]);

        $msg = new Message();
        $msg->sender_id = $request->sender_id;
        $msg->reciever_id = $request->reciever_id;
        $msg->message = $request->message;

        if ($msg->save()) {
            return response()->json(['status' => true, 'message' => 'Message Added']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
    }
    public function get_msg(Request $request, $id)
    {
        $msg = db::select("SELECT u1.name,u1.image, messages.*  FROM `messages`
    join users as u1 on u1.id = messages.sender_id
     where reciever_id = $id
     group by messages.sender_id");

        return response()->json($msg);
    }

    public function get_msgchat($id, $id1)
    {
        $msg = db::select("SELECT u1.name as sender, u2.name as reciever, messages.message  FROM `messages`
    join users as u1 on u1.id = messages.sender_id
    join users as u2 on u2.id = messages.reciever_id
    where (reciever_id = $id and sender_id = $id1) or (reciever_id = $id1 and sender_id = $id)");

        return response()->json($msg);
    }

    public function getSurveyList($uid, $id)
    {

        $surveylist = db::select(db::raw("select * from survey_details
 join surveys on survey_details.survey_id = surveys.id
 join users on users.id = survey_details.user_id
 where user_id = $uid and survey_id = $id"));

        if ($surveylist) {
            return response()->json($surveylist);
        } else {
            return response()->json(['message' => 'لا توجد قائمة المسح']);
        }
    }

    public function getDropdownUsers($id)
    {
        $users = db::select(db::raw("SELECT id, name,image FROM users WHERE id != $id"));
        if ($users) {
            return response()->json($users);
        } else {
            return response()->json(['message' => 'ERROR.......']);
        }
    }

    public function post_view()
    {
        $post = db::select(db::raw("Select * from posts order by id desc"));
        $arr = array();
        foreach ($post as $posts) {
            // $posts->image;
            array_push($arr, $posts->image);
        }
        return response()->json(['status' => true, 'post' => $post]);
    }

    public function getAbout()
    {
        $aboutus = db::select(db::raw("Select * from about_us"));
        return response()->json(['data' => $aboutus]);
    }

    public function getSponsars()
    {
        $sponsars = db::select(db::raw("Select * from sponsors"));
        return response()->json(['data' => $sponsars]);
    }

    public function getprivacies()
    {
        $privacies = db::select(db::raw("Select * from privacies"));
        return response()->json(['data' => $privacies]);
    }

    public function checkBid(Request $request)
    {
        $request->validate([

            'user_id' => 'required',
            'post_id' => 'required',

        ]);

        $checkBids = db::select(db::raw("Select * from post_bids where post_id = $request->post_id and user_id= $request->user_id"));
        if ($checkBids) {
            return response()->json(['status' => "Already Exists"]);
        } else {
            return response()->json(['status' => "Not Exists"]);
        }
    }

    public function withdrawBids($id)
    {
        $bid = PostBid::where('id', $id)->first();
        $post = Post::where('id', $bid->post_id)->first();
        $post_price = $post->price;
        $bid_amount = $bid->price;
        // if($post_price < $bid_amount)
        if($post)
        {
            PostBid::where('id', $id)->delete();
            return response()->json(['status' => "Successfully Delete"]);
        }else
        {
            return response()->json(['status' => 'ERROR.......']);
        }
        
       }

    public function getBank()
    {
        $bank = db::select(db::raw("Select * from banks"));
        if ($bank) {
            return response()->json(['status' => $bank]);
        } else {
            return response()->json(['status' => "ERROR......"]);
        }
    }

    public function competition_winner(Request $request)
    {
        $request->validate([
            'competition_id' => 'required',
            //   'user_id'          => 'required',
            //   'post_id'          => 'required'

        ]);

        //  $competition_winner = db::select(db::raw("SELECT competition_winners.*, posts.like_count FROM `competition_winners`
        //  join users on users.id = competition_winners.user_id
        //  join competitions on competitions.id = competition_winners.competition_id
        //  join posts on posts.id = competition_winners.post_id
        //  where competition_winners.competition_id = $request->competition_id and competition_winners.user_id = $request->user_id and post_id = $request->post_id
        //  order by posts.like_count desc
        //  "));

        $competition_winner = db::select(db::raw("SELECT competition_winners.*, posts.like_count, users.image FROM `competition_winners`
        join users on users.id = competition_winners.user_id
        join competitions on competitions.id = competition_winners.competition_id
        join posts on posts.id = competition_winners.post_id
        where competition_winners.competition_id = $request->competition_id
        order by posts.like_count desc"));

        if ($competition_winner) {
            return response()->json(['status' => $competition_winner]);
        } else {
            return response()->json(['status' => "ERROR......."]);
        }
    }

    public function getPostByCategories($id)
    {

        $categories = db::select(db::raw("SELECT * FROM `posts` WHERE `category_id` in (6, 8, 2) And `price_type` = 'سوم' And user_id = $id "));
        if ($categories) {
            return response()->json(['status' => $categories]);
        } else {
            return response()->json(['status' => 'ERROR.........']);
        }
    }

    public function getBidsByPostId($id)
    {

        $getBids = db::select(db::raw("select users.* , posts.*, post_bids.* from post_bids
                                  join posts on posts.id = post_bids.post_id
                                  join users on users.id = post_bids.user_id
                                  where post_bids.post_id = $id "));
        if ($getBids) {
            return response()->json(['status' => $getBids]);
        }
        else
        {
        return response()->json(['status'=>'No Record Found']);
        }

    }

    public function get_about()
    {
        $about = db::select(db::raw("select * from about_us where id = 7"));
        if ($about) {
            return response()->json(['stastus' => $about]);
        } else {
            return response()->json(['status' => 'ERROR............']);
        }
    }

    public function get_privacy()
    {
        $privacy = db::select(db::raw("select * from privacies where id = 1"));
        if ($privacy) {
            return response()->json(['status' => $privacy]);
        } else {
            return response()->json(['status' => 'ERROR..........']);
        }
    }

    public function get_postclub(Request $request)
    {
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }

            
        $post_likes_ = PostLike::where('user_id',$request->user_id)->get(); 
        $camel = db::select(db::raw("select posts.*,categories.name as category_name,users.phone as user_phonee,users.phone_status as phone_status,users.whatsapp_status as whatsapp_status,users.chat_status as chat_status, users.image as user_images,users.name as name
                                        from posts
                                        join users on users.id = posts.user_id
                                        join categories on categories.id= posts.category_id where posts.category_id = 1
                                        order by posts.id desc"));

        $return_arr = array();
        foreach ($camel as $imgs) {
            $id = $imgs->id;
            $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $imgs->id)
                    ->select('thumbnail')
                    ->first();
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $chat_status = $imgs->chat_status;
            $whatsapp_status = $imgs->whatsapp_status;
            $phone_status = $imgs->phone_status;
            $user_phone = $imgs->user_phonee;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $name = $imgs->name;
            $flagForLike= false;
          
           if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
            {
                if ($post_likes_s->post_id === $id) {
                 $flagForLike = true;
              
                }
            }
            }

            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id,'thumbnail'=>$thumbnail, 'user_id' => $user_id, 'user_name' => $user_name,'phone_status'=>$phone_status, 'whatsapp_status'=>$whatsapp_status,'chat_status'=>$chat_status, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at,
                'updated_at' => $updated_at, 'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images,
                'name' => $name, 'flagForLike'=>$flagForLike
            );
        }

        return response()->json(['Posts' => $return_arr]);
    }

    public function award_bids(Request $request)
    {
        $this->validate($request, [
            'bid_id' => 'required',
            'user_id' => 'required',
            'post_id' => 'required',

        ]);
        //  $check = PostBid::where('user_id', $request->user_id)->where('post_id', $request->post_id)->where('id', $request->bid_id)->first();
        $checkaward = Post::where('id', $request->post_id)->where('bid_status', 1)->first();
        if ($checkaward) {
            return response()->json(['status' => false, 'message' => 'منحت بالفعل']);
        } else {
            $post = post::find($request->post_id);
            $post->bid_status = 1;

            //  $user = User::find($request->user_id);
            $award_bids = new awardbids();
            $award_bids->bid_id = $request->bid_id;
            $award_bids->user_id = $request->user_id;
            $award_bids->post_id = $request->post_id;

            if ($award_bids->save() && $post->save()) {
                return response()->json(['status' => true, 'message' => $award_bids, 'bid' => $post]);
            } else {
                return response()->json(['status' => 'ERROR..........']);
            }
        }
        //  $bid = PostBid::find($request->bid_id);
        //  $bid->status = 1;

    }

    public function send_sms_old(Request $request)
    {

        $phone = $request->phone;
        $message = $request->message;

        $curl_handle = curl_init();

        $sms_query = 'https://api.smsglobal.com/http-api.php?action=sendsms&user=78eu1194&password=OUapXpvP&from=Test&to=' . $phone . '&text=' . $message;

        curl_setopt($curl_handle, CURLOPT_URL,  $sms_query);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);

        if ($buffer) {
            return response()->json(['status' => true, 'result' => $buffer]);
        } else {
            return response()->json(['status' => false]);
        }
    }
    
    
   public function send_sms_oldd(Request $request, MsegatService $msegatService)
    {
        $phone = $request->phone;
        $message = $request->message;
    
        // Use the Msegat service to send the SMS
          
        $response = $msegatService->sendSMS($phone, $message);
    
        // Debug the response
         dd($response);
    
        if ($response['code'] === 'M0000') { // Check the success code
            return response()->json(['status' => true, 'result' => $response['message']]);
        } else {
            return response()->json(['status' => false, 'error' => $response['message']]);
        }
    }


    // in this apii something is wrong
    public function send_sms(Request $request)
    {  
       $phone = $request->phone;
     
       $otpCode = mt_rand(1000, 9999);
       $otp_created_at = now()->addMinutes(3);
       
    //   $user = User::where('phone', $phone)->update(['otp_code'=> $otpCode, 'otp_created_at'=>$otp_created_at ]);
         $check = User::where('phone',$phone)->first();
         if($check){
             return response()->json(['status'=> false, 'message'=> 'رقم الهاتف موجود بالفعل']);
         }else{
             $user= new User();
             $user->phone = $phone;
             $user->otp_code= $otpCode;
             $user->otp_created_at = $otp_created_at;
             
            if($user->save()){
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://www.msegat.com/gw/sendsms.php',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>'{
              "userName":"Tasdeertech",
              "numbers": "'.$phone.'",
              "userSender":"Tasdeer",
              "apiKey":"04e664c9c237f7853c5a9a0276a539a5",
              "msg":"Your OTP code is: '.$otpCode.'"
            }', 
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: PHPSESSID=mo584383b69aqsq9aj5onp5lk3; SERVERID=MBE2; userCurrency=SAR; userLang=Ar'
              ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            echo $response;
       }else
       {
           return response()->json(['success'=>false]);
       }
         }
         
    
      
    }
    
    public function reset_otp(Request $request){
        
       $phone = $request->phone;
       $otpCode = mt_rand(1000, 9999);
       $otp_created_at = now()->addMinutes(3);
       $check = User::where('phone',$phone)->first();
      
       if(empty($check)){
             return response()->json(['status'=> false, 'message'=> 'رقم الهاتف غير موجود']);
         }else{
             $user = User::where('phone', $phone)->update(['otp_code'=> $otpCode, 'otp_created_at'=>$otp_created_at ]);
              
            if($user==1){
                 
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://www.msegat.com/gw/sendsms.php',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>'{
              "userName":"Tasdeertech",
              "numbers": "'.$phone.'",
              "userSender":"Tasdeer",
              "apiKey":"04e664c9c237f7853c5a9a0276a539a5",
              "msg":"Your OTP code is: '.$otpCode.'"
            }', 
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: PHPSESSID=mo584383b69aqsq9aj5onp5lk3; SERVERID=MBE2; userCurrency=SAR; userLang=Ar'
              ),
            )); 
          
            $response = curl_exec($curl);
            
            curl_close($curl);
          
            echo $response;
    
       }else
       {
           return response()->json(['success'=>false]);
       }
         }
        
    }
    
    public function check_otp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required', 
            'phone' => 'required'
            
            ]);
            
            $user = User::where('phone', $request->phone)->first();
            $otp_code= $user->otp_code;
            
            if($request->otp_code != $otp_code){
                return response()->json(['success'=>false,'message'=>'رمز OTP الخاص بك غير مطابق']);
            }
          
            if(!empty($otp_code))
            {
                if(strtotime(date('Y-m-d H:i:s')) < strtotime($user->otp_created_at)){
                    
                    $user_update = User::where('phone', $request->phone)->update(['otp_code'=> $request->otp_code]);
                if($user_update == 1)
                {
                    $user_details = User::where('phone', $request->phone)->first();
                    return response()->json(['success'=> true,'message'=> 'تم التحقق من Otp بنجاح', 'user_details'=>$user_details]);
                }
                
                }else
                    {
                        $user = User::where('phone', $request->phone)->delete();
                        return response()->json(['success'=> false,'message'=> 'لقد انتهت صلاحية رمز OTP الخاص بك!']);
                    }
                
                
            }else
            {
                return response()->json(['success'=>false,'message'=>'لم يتم العثور على رمز OTP']);
            }
            
    }

    public function add_whatsapp(Request $request, $id)
    {
        $whatsapp = User::where('id', $id)->first();
        $whatsapp->whatsapp_no = $request->whatsapp_no;
        if ($whatsapp->save()) {
            return response()->json(['status' => true, 'message' => 'تمت إضافة رقم الواتساب بنجاح']);
        } else {
            return response()->json(['status' => false, 'message' => 'ERROR..................']);
        }
    }

    public function notification($id)
    {
        $notification = Notification::where('receiver_id', $id)->orderBy('id', 'DESC')->get();
        
     

        
        $return_arr = array();
        
        if ($notification) {
            foreach($notification as $notifications)
            {
                 $id = $notifications->id;
                 $description = $notifications->description;
                 $sender_id= $notifications->sender_id;
                 $receiver_id = $notifications->receiver_id;
                 $post_id = $notifications->post_id;
                 $type = $notifications->type;
                 $created_at = $notifications->created_at;
                 $updated_at = $notifications->updated_at;
                 
                 $sender_details = User::where('id',$notifications->sender_id)->first();
                 $sender_name = $sender_details->name ?? null;
                 $sender_image = $sender_details->image ?? null;
                 if($notifications->post_id){
                 $post_detail = Post::where('id', $notifications->post_id)->first();
                 $bid_status = $post_detail->bid_status ?? null;
                 }else{
                     $bid_status=null;
                 }
                 
                 
                 $return_arr[] = array('id'=>$id,'description'=>$description,'sender_id'=>$sender_id,'receiver_id'=>$receiver_id,
                 'post_id'=>$post_id,'type'=>$type,'created_at'=>$created_at,'updated_at'=>$updated_at,
                 'sender_name'=>$sender_name, 'sender_image'=>$sender_image,'bid_status'=>$bid_status);
            }
                   
           
            
            return response()->json(['success' => true, 'notification'=>$return_arr]);
        } else {
            return response()->json(['success' => false]);
        }
    }


    public function survey()
    {
        $survey = Db::select(Db::raw("SELECT * FROM `surveys` "));
        $survey_detail = Db::select(Db::raw("SELECT * FROM `survey_details` "));
        $dataSet = array();

        foreach ($survey as $surveys) {


            $data = array();

            foreach ($survey_detail as $surveys_details) {

                if ($surveys->id == $surveys_details->survey_id) {
                    array_push($data, $surveys_details);
                }
            }

            $dataSett = ['survey' => $surveys, 'survey_questions' => $data];
            array_push($dataSet, $dataSett);
        }
        // dd($dataSet);
        return response()->json(['data' => $dataSet]);
    }

    public function add_advertisement(Request $request)
    {

        $request->validate([
            'title' => 'required',
            'image' => 'required'
        ]);

        $advertisement = new Advertisment();
        //   dd($request->all());
        $image_64 = $request->image;

        $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; // .jpg .png .pdf

        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

        $image = str_replace($replace, '', $image_64);

        $image = str_replace(' ', '+', $image);

        $imageName = Str::random(10) . '.' . $extension;

        Storage::disk('public')->put('images/advertisement' . $imageName, base64_decode($image));

        $advertisement->title = $request->title;
        $advertisement->image = $imageName;
        //   dd($advertisement);
        if ($advertisement->save()) {
            return response()->json(['status' => true, 'message' => 'Advertisement has been added successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error in adding advertisement']);
        }
    }

    public function get_advertisement()
    {
        $advertisement = Advertisment::orderBy('id','DESC')->get();
       
        if ($advertisement) {
            return response()->json(['status' => true, 'advertisement' => $advertisement]);
        } else {
            return response()->json(['status' => false, 'message' => 'ERROR.........']);
        }
    }

    public function add_newslike(Request $request)
    {
        $request->validate([
            'news_id' => 'required',
            'user_id' => 'required',

        ]);

        $user = User::find($request->user_id);
        $news_like = new NewsLike();
        $news_like->user_id = $user->id;

        $news_like->news_id = $request->news_id;
        if ($news_like->save()) {
            return response()->json(['status' => true, 'message' => 'Like Added']);
        } else {
            return response()->json(['status' => false, 'message' => 'Error Occurred']);
        }
    }

    public function get_news_like()
    {
        $news_like = db::select(db::raw("SELECT news_id, users.name, users.image FROM `news_likes`
    join users on users.id= news_likes.user_id
    order by news_id desc"));
        if ($news_like) {
            return response()->json(['status' => true, 'likes' => $news_like]);
        } else {
            return response()->json(['status' => false, 'message' => 'ERROR...........']);
        }
    }

    public function news_comment_like(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'news_comment_id' => 'required',
        ]);

        $check = NewsCommentLike::where('news_comment_id', $request->news_comment_id)->where('user_id', $request->user_id)->first();
        if ($check) {
            if ($check->delete()) {
                $news_comment = NewsComment::where('id', $request->news_comment_id)->first();
                $news_comment->comment_like_count = (int) $news_comment->comment_like_count - 1;
                $news_comment->save();
                
                $total_like = NewsComment::where('id', $request->news_comment_id)->first();
                $total_likes = $total_like->comment_like_count;
                
                
                return response()->json(['status' => true, 'message' => 'Successfully Unliked', 'total_likes'=>$total_likes]);
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        } else {
            $like = new NewsCommentLike();
            $like->news_comment_id = $request->news_comment_id;
            $like->user_id = $request->user_id;
            $news_comment = NewsComment::where('id', $request->news_comment_id)->first();
            $news_comment->comment_like_count = (int) $news_comment->comment_like_count + 1;
            
            if ($like->save() && $news_comment->save()) {
                $total_like = NewsComment::where('id', $request->news_comment_id)->first();
                $total_likes = $total_like->comment_like_count;
                return response()->json(['status' => true, 'message' => 'Successfully liked', 'total_likes'=>$total_likes]);
            } else {
                return response()->json(['status' => false, 'message' => 'Error Occurred']);
            }
        }
    }
    
    
    public function check_survey_by_user_id(Request $request)
    {
        $request->validate([
            
            'user_id' => 'required',
            'survey_id'=> 'required',
            ]);
            
            $survey_submit = SurveySubmit::where(['user_id'=>$request->user_id,'survey_id'=>$request->survey_id])->get();
            
            $survey= array();
            foreach($survey_submit as $survey_submits)
            {
                $survey[] = $survey_submits;
            }
            if(!empty($survey))
            {
                return response()->json(['status'=>true]);
            }else
            {
                return response()->json(['status'=>false]);
            }
            
    }
    
    
    public function CheckFriendShipStatus($user_id,$friend_id)
    {
       
    
        $check_friend_status = Friend::where(['user_id'=>$user_id,'friend_id'=>$friend_id])->first();
   
        if(!empty($check_friend_status))
        { 
            if($check_friend_status->status == 'R' || $check_friend_status->status == 'C')
            {
                return response()->json(['status'=>null]); 
            }else
            {
                 return response()->json(['status'=>$check_friend_status->status]);
            }
               
           
           
        }else
        {
             $check_friend_status = Friend::where(['user_id'=>$friend_id,'friend_id'=>$user_id])->first(); 
           
             if(!empty($check_friend_status))
             {  
                 if($check_friend_status->status == 'A')
                 {
                    return response()->json(['status'=>'A']);
                 }else{
                   
                  return response()->json(['status'=>'AR']);
                 }
                 
             }else{
                  return response()->json(['status'=>null]);
             }
               
            
        }
        // if($check_friend_status)
        // {
        //     return response()->json(['status'=>$check_friend_status->status]);
        // }else
        // {
        //      return response()->json(['status'=>null]);
        // }
    }
    
    public function friendRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'friend_id' => 'required',
            'status'=> 'required'
            
            ]);
            
            $friendship_check = Friend::where(['user_id'=> $request->user_id, 'friend_id'=>$request->friend_id])->first();
            $reveerse_friendship_check = Friend::where(['user_id'=> $request->friend_id, 'friend_id'=>$request->user_id])->first();
            // here friendship_check
            if($friendship_check)
            {  
                
                if($request->status == 'A')
               {
                   $change_status = Friend::where(['user_id'=> $request->user_id, 'friend_id'=>$request->friend_id])->update(['status'=> $request->status]);
                       if($change_status == 1)
                       {
                         return response()->json(['status'=>'successfully updated']); 
                       }else
                       {
                          return response()->json(['status'=>'failed']); 
                       }
               }
               elseif($request->status == 'C' || $request->status == 'R')
               {
                    $change_status = Friend::where(['user_id'=> $request->user_id, 'friend_id'=>$request->friend_id])->delete();
                     if($change_status == 1)
                           {
                              return response()->json(['status'=>'successfully updated']); 
                           }else
                           {
                              return response()->json(['status'=>'failed']); 
                           }
               }
            
               
                
            }
            
            
            
            
            // here reveerse_friendship_check
            elseif($reveerse_friendship_check)
            {
                if($request->status == 'A' )
               {
                          $change_status = Friend::where(['user_id'=> $request->friend_id, 'friend_id'=>$request->user_id])->update(['status'=> $request->status]);
                           if($change_status == 1)
                           {
                               
                           $friend_id = $request->user_id;
                           $firend_data = User::where('id', $friend_id)->first();
                           $firend_device_token = $firend_data->device_token;
                           $friend_name = $firend_data->name;
                           
                           $user_id = $request->friend_id;
                           $user_data = User::where('id', $user_id)->first();
                           $user_name = $user_data->name;
                           $user_device_token= $user_data->device_token;
                           
                           $curl = curl_init();

                            curl_setopt_array($curl, array(
                              CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_ENCODING => '',
                              CURLOPT_MAXREDIRS => 10,
                              CURLOPT_TIMEOUT => 0,
                              CURLOPT_FOLLOWLOCATION => true,
                              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                              CURLOPT_CUSTOMREQUEST => 'POST',
                              CURLOPT_POSTFIELDS =>'{
                             "to" : "'.$user_device_token.'",
                             "notification" : {
                                 
                                 "body": "'.$friend_name.' accepts your friend request"
                             }
                            }',
                              CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'Authorization: key='.$this->firebase_key.''
                              ),
                            ));
                            
                            $response = curl_exec($curl);
                              curl_close($curl);
                              
                            $notification_desc='لققد قبل طلب الصداقة الخاص بك';
                            DB::table('notifications') ->insert([
                            'description' => $notification_desc,
                            'sender_id'=> $request->user_id,
                            'receiver_id'=>$request->friend_id,
                            'type'=>'friend request'
                            ]); 
                           
                              return response()->json(['status'=>'successfully updated', 'response' => $response]); 
                           }else
                           {
                              return response()->json(['status'=>'failed']); 
                           }
               } 
               elseif($request->status == 'C' || $request->status == 'R')
               {
                    $change_status = Friend::where(['user_id'=> $request->friend_id, 'friend_id'=>$request->user_id])->delete();
                     if($change_status == 1)
                           {
                              return response()->json(['status'=>'successfully updated']); 
                           }else
                           {
                              return response()->json(['status'=>'failed']); 
                           }
               } 
            }
            
            // here friends add
            else
            {
                $friend = new Friend();
                $friend->user_id = $request->user_id;
                $friend->friend_id = $request->friend_id;
                $friend->status= $request->status;
                if($friend->save())
                { 
                           $friend_id = $request->friend_id;
                           $firend_data = User::where('id', $friend_id)->first();
                           $firend_device_token = $firend_data->device_token;
                           $friend_name = $firend_data->name;
                            
                           $user_id = $request->user_id;
                           $user_data = User::where('id', $user_id)->first();
                           $user_name = $user_data->name;
                           $user_device_token= $user_data->device_token;
                           
                           $curl = curl_init();

                            curl_setopt_array($curl, array(
                              CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                              CURLOPT_RETURNTRANSFER => true,
                              CURLOPT_ENCODING => '',
                              CURLOPT_MAXREDIRS => 10,
                              CURLOPT_TIMEOUT => 0,
                              CURLOPT_FOLLOWLOCATION => true,
                              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                              CURLOPT_CUSTOMREQUEST => 'POST',
                              CURLOPT_POSTFIELDS =>'{
                             "to" : "'.$firend_device_token.'",
                             "notification" : {
                                 "body" : "'.$user_name.' send you freind request!"
                                 
                             }
                            }',
                              CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'Authorization: key='.$this->firebase_key.''
                              ),
                            ));
                            
                            
                            $notification_desc='لقد أرسل لك طلب صداقة';
                            DB::table('notifications') ->insert([
                            'description' => $notification_desc,
                            'sender_id'=> $request->user_id,
                            'receiver_id'=>$request->friend_id,
                            'type'=>'friend request'
                            ]); 
                            
                            
                            
                            $response = curl_exec($curl);
                            curl_close($curl);
                            
                            
                            
                            
                            return response()->json(['status'=>'successfully added', 'response'=>$response]); 
                    
                }else
               {
                  return response()->json(['status'=>'failed']); 
               }
            }
    }
    


   public function get_bids_by_user_id_post_id($user_id)
   {
       
     $post_likes_ = PostLike::where('user_id',$user_id)->get();
     $posts = Post::where('user_id', $user_id)->with('bids')->orderBy('id', 'DESC')->get();
   
    

     $formattedData = $posts->flatMap(function ($post) use ($post_likes_){
        return $post->bids->map(function ($bid) use ($post, $post_likes_) {
             $arr = explode(",", $post->image);
             $posting_data= Post::where('id', $post->id)->first();
             $highest_bid = DB::table('post_bids')->where('post_id', $post->id)->max('price');
             $thumbnail = DB::table('post_thumbnail')
                    ->where('post_id', $post->id)
                    ->select('thumbnail')
                    ->first();
             
             $flagForLike= false;
            if($post_likes_){
                foreach($post_likes_ as $post_likes_s)
                {
                   if ($post_likes_s->post_id === $post->id) {
                    //   put condition there which bid price is highest
                    
                      $flagForLike = true;
                    }
              }
            }
            $formattedBid = [
                
                // Include other bid attributes here
                    'bid_id' => $bid->id,
                    'bid_user_name' => $bid->user->name,
                    'user_image' => $bid->user->image,
                    'user_id' => $bid->user_id,
                    'post_id' => $bid->post_id,
                    'price' => $bid->price,
                    'bid_status' => $bid->bid_status,
                    'created_at' => $bid->created_at,
                    'updated_at' => $bid->updated_at,
                   
                    'post' => [
                         // Include other post attributes here
                         
                        'post_id' => $post->id,
                        'user_id' => $post->user_id,
                        'thumbnail'=>$thumbnail,
                        'name' => $post->user_name,
                        'user_phone' => $post->user_phone,
                        'user_email' => $post->user_email,
                        'user_images' => $post->user->image,
                        'user_location' => $post->user->location,
                        'category_id' => $post->category_id,
                        'to_location' => $post->to_location,
                        'title' => $post->title,
                        'location' => $post->location,
                        'color' => $post->color,
                        'camel_type' => $post->camel_type,
                        'activity' => $post->activity,
                        'car_model' => $post->car_model,
                        'car_type' => $post->car_type,
                        'price' => $post->price,
                        'bid_price' => $highest_bid,
                        'price_type' => $post->price_type,
                        'date' => $post->date,
                        'video' => $post->video,
                        'age' => $post->age,
                        'description' => $post->description,
                        'competition_id' => $post->competition_id,
                        'register' => $post->register,
                        'account_activity' => $post->account_activity,
                        'status' => $post->status,
                        'moving_camel_amount' => $post->moving_camel_amount,
                        'view_count' => $post->view_count,
                        'share_count' => $post->share_count,
                        'like_count' => $post->like_count,
                        'comment_count' => $post->comment_count,
                        'created_at' => $post->created_at,
                        'updated_at' => $post->updated_at,
                        'commission' => $post->commission,
                        'flagForLike' => $flagForLike,
                        'img' => $arr,
                        
                        'bid_status' => $post->bid_status,
                       
                    ],
              
            ];
            // return ['formatedata'=>$formattedBid,'posting_data'=>$posting_data];
            return $formattedBid;
        });
    });

    return response()->json($formattedData);
       
      
   }
   
   public function category_filter_by_user_id(Request $request)
   {
      $request->validate([
           
           'user_id'=> 'required',
           'category_id' => 'required'
           
           ]);
           
        $post_likes_ = '';
         
        if($request->user_id)
        {
          $post_likes_ = PostLike::where('user_id',$request->user_id)->get();  
        }
           
           $posts = db::select(db::raw("select posts.*,posts.id as post_id,categories.name as category_name, users.image as user_images,users.name as name
                                        from posts
                                        join users on users.id = posts.user_id
                                        join categories on categories.id= posts.category_id 
                                        where posts.user_id = $request->user_id and posts.category_id = $request->category_id
                                        order by posts.id desc"));
           
           $return_arr = array();
           
        foreach ($posts as $imgs) {
            // $id = $imgs->id;
            $id = $imgs->post_id;
            $user_id = $imgs->user_id;
            $user_name = $imgs->user_name;
            $user_phone = $imgs->user_phone;
            $user_email = $imgs->user_email;
            $category_id = $imgs->category_id;
            $to_location = $imgs->to_location;
            $title = $imgs->title;
            $location = $imgs->location;
            $color = $imgs->color;
            $camel_type = $imgs->camel_type;
            $activity = $imgs->activity;
            $car_model = $imgs->car_model;
            $car_type = $imgs->car_type;
            $price = $imgs->price;
            $price_type = $imgs->price_type;
            $date = $imgs->date;
            $video = $imgs->video;
            $age = $imgs->age;
            $description = $imgs->description;
            $competition_id = $imgs->competition_id;
            $register = $imgs->register;
            $account_activity = $imgs->account_activity;
            $status = $imgs->status;
            $moving_camel_amount = $imgs->moving_camel_amount;
            $view_count = $imgs->view_count;
            $share_count = $imgs->share_count;
            $like_count = $imgs->like_count;
            $comment_count = $imgs->comment_count;
            $created_at = $imgs->created_at;
            $updated_at = $imgs->updated_at;
            $commission = $imgs->commission;
            $category_name = $imgs->category_name;
            $user_images = $imgs->user_images;
            $name = $imgs->name;
            $flagForLike= false;
            if(!empty($post_likes_)){
                foreach($post_likes_ as $post_likes_s)
                {
                    if ($post_likes_s->post_id === $id) {
                     $flagForLike = true;
                    }
                }
            }
            
            $arr = explode(",", $imgs->image);
            $return_arr[] = array(
                'img' => $arr, 'id' => $id, 'user_id' => $user_id, 'user_name' => $user_name, 'user_phone' => $user_phone,
                'user_email' => $user_email, 'category_id' => $category_id, 'to_location' => $to_location, 'title' => $title, 'location' => $location,
                'color' => $color, 'camel_type' => $camel_type, 'activity' => $activity, 'car_model' => $car_model, 'car_type' => $car_type, 'price' => $price,
                'price_type' => $price_type, 'date' => $date, 'video' => $video, 'age' => $age, 'description' => $description, 'competition_id' => $competition_id,
                'register' => $register, 'account_activity' => $account_activity, 'status' => $status, 'moving_camel_amount' => $moving_camel_amount, 'view_count' => $view_count,
                'share_count' => $share_count, 'like_count' => $like_count, 'comment_count' => $comment_count, 'created_at' => $created_at, 'updated_at' => $updated_at,
                'commission' => $commission, 'category_name' => $category_name, 'user_images' => $user_images, 'name' => $name,'flagForLike'=>$flagForLike
            );
        }
        
        return response()->json(['status'=> true, 'posts'=>$return_arr]);
           
   }
   
   
   public function getFriendRequest($id)
   {
       $friend = DB::select(DB::raw("SELECT friends.id as F_id, friends.user_id as friend_id, friends.friend_id as user_id, friends.status as f_status, friends.created_at as created_at, friends.updated_at as updated_at, users.name as friend_name, users.image as friend_image  FROM `friends` 
                                     left join users on users.id = friends.user_id
                                     WHERE friends.`friend_id` = $id and friends.`status` = 'P' "));
       if($friend)
       {
           return response()->json(['status'=> true, 'FriendRequest'=>$friend]);
       }else
       {
           return response()->json(['status'=> true, 'message'=>'لم يتم العثور على أي طلب']);
       }
   }

   public function news_comments_likes_by_user_id(Request $request)
   {
      $request->validate([
          
          'news_id' => 'required'
          
          ]); 
          
         $user_id = $request->user_id;
         $comments_likes = '';
         
         if($user_id)
         {
            $comments_likes =  NewsCommentLike::where(['user_id'=> $user_id])->get();
            $news_rating = NewsRating::where(['user_id'=> $user_id])->get();
         }
         
         $news_comments = DB::select(DB::raw("SELECT news_comments.id as news_comment_id,users.image as user_image, news_comments.*,news.*,users.*  FROM `news_comments` 
                                              left join news on news.id = news_comments.news_id
                                              left join users on users.id = news_comments.user_id
                                              where news.id = $request->news_id order by news_comments.id desc"));
                                              
        $return_arr = array();
        
         foreach($news_comments as $news_comments)
         {
           $news_comments_id = $news_comments->news_comment_id;    
           $news_id= $news_comments->news_id;
           $user_id= $news_comments->user_id;
           $user_name = $news_comments->name;
           $user_image = $news_comments->image;
           $comment= $news_comments->comment;
           $comment_like_count= $news_comments->comment_like_count;
           $created_at= $news_comments->created_at;
           $updated_at= $news_comments->updated_at;
           $flagForLike = false;
           if(!empty($comments_likes))
           {
               foreach($comments_likes as $comments_likes_s)
               {
                  if($comments_likes_s->news_comment_id == $news_comments_id)
                  {
                      $flagForLike = true;
                  }
               }
           }
           
           $flagForRating = false;
           if(!empty($news_rating))
           {
               foreach($news_rating as $news_ratings)
               {
                   if($news_ratings->news_id == $news_id)
                   {
                       $flagForRating = true;
                   }
               }
           }
           $return_arr[]= array('news_comments_id'=> $news_comments_id,'news_id' => $news_id, 'user_id'=> $user_id, 'comment'=> $comment,'user_name'=>$user_name,
          'user_image'=>$user_image, 'comment_like_count'=> $comment_like_count, 'created_at'=> $created_at, 'updated_at'=> $updated_at,
          'flagForLike'=>$flagForLike, 'flagForRating'=>$flagForRating);
           
             
         }
         return response()->json($return_arr);
   }
   
   
   public function friendList($id)
   {
       $Total_friends=[];
       $friends=Friend::where('status','A')->Where('friend_id',$id)->get();
       $Rfriends=Friend::where('status','A')->Where('user_id',$id)->get();
       foreach($friends as $f){
       $Total_friends[] =$f;   
       }
       foreach($Rfriends as $f){
       $Total_friends[] =$f;   
       }
   
       
       if(!$Total_friends){
            return response()->json(['msg'=> 'No friends found!']);
       }
          
       $requiredUser=[];
       foreach($Total_friends as $friend){
          if($friend->user_id ==$id){
              $friend['user_id']=$friend['friend_id'];
              $friend['friend_id']=$friend['user_id'];
              $requiredUser[]=User::where('id',$friend['user_id'])->first();
          }else{
          $requiredUser[]=User::where('id',$friend->user_id)->first();
          }
          $return_arr=[];
       }

       foreach ($requiredUser as $requiredFriend) {
       $return_arr[] = [
        'firend_id' => $requiredFriend['id'] ?? null,
        'firend_name' => $requiredFriend['name'] ?? null,
        'firend_image' => $requiredFriend['image' ?? null]
       ]; 
    }

           
    

// return $return_arr;
if(count($return_arr)>0){
        return response($return_arr);
    
}else{
    
     return response()->json(['msg'=> 'No friends found!']);
}


     
       
       
       
    //   $checkFriend = Friend::select('friends.user_id','friends.friend_id','friend_user.name as friend_name',
    //                   'sender_user.name as sender_name','sender_user.image as sender_image','friend_user.image as friend_image')
    //                   ->where('friends.status','A')->where('friends.user_id', $id)->orWhere('friends.friend_id',$id)
    //                   ->join('users as friend_user','friend_user.id','=','friends.user_id')
    //                   ->join('users as sender_user','sender_user.id','=','friends.friend_id')->get();
         
    //   $return_arr = array();
    //   $list = array();
    //   if(!empty($checkFriend))
    //   {
    //       foreach($checkFriend as $friend)
    //       {  
    //           if($friend->friend_id == $id){
    //               $firend_id     = $friend->user_id;
    //               $firend_name   = $friend->sender_name;
    //               $firend_image  = $friend->sender_image;
    //               $list[] = $firend_id;
    //               $return_arr[]= array(
    //                   'firend_id'=>$firend_id,
    //                   'firend_name'=>$firend_name,
    //                   'firend_image'=>$firend_image
    //                   );
                   
    //             }
    //             if($friend->user_id == $id){
    //                   $firend_id    = $friend->friend_id;
    //                   $firend_name  = $friend->friend_name;
    //                   $firend_image = $friend->friend_image;
    //                 $list[] = $firend_id;
    //                 $return_arr[]= array(
    //                     'firend_id'=>$firend_id,
    //                     'firend_name'=>$firend_name,
    //                     'firend_image'=>$firend_image
    //             );
    //             }
    //       }
    //     // dd(array_unique(array_column($return_arr, 'firend_id')));
    //       return response($return_arr);
    //   }else
    //   {
    //       return response()->json(['msg'=> 'No friends found!']);
    //   }
   }
   
   public function getMultipleUsersDetails(Request $request)
   {
    
            $jsonData = $request->json()->all();
        
            if (is_array($jsonData) && count($jsonData) > 0) {
                $return_arr = [];
        
                foreach ($jsonData as $user_data) {
                    $user_id = $user_data['id'];
                    $user_message = $user_data['message'];
                    
                    
                    $user = User::find($user_id);
        
                    if ($user) {
                        $id = $user->id;
                        $user_name = $user->name;
                        $user_image = $user->image;
        
                        $return_arr[] = [
                            'id' => $id,
                            'user_name' => $user_name,
                            'user_image' => $user_image,
                            'message' => $user_message
                        ];
                    }
                }
        
                return response()->json($return_arr);
                
            } else {
                return response()->json(['error' => 'Invalid JSON data format'], 400);
            }
    
    
   }
   
   public function add_block(Request $request)
   {
     $request->validate([
         
         'user_id' => 'required',
         'friend_id' => 'required',
         'status' => 'required'
         
         ]);  
         
        $block_user = new Block(); 
        $block_user->user_id = $request->user_id;
        $block_user->friend_id = $request->friend_id;
        $block_user->is_block = 1;
        if($block_user->save())
        {
            $friendship_check = Friend::where(['user_id'=> $request->user_id, 'friend_id'=>$request->friend_id])->first();
            $reveerse_friendship_check = Friend::where(['user_id'=> $request->friend_id, 'friend_id'=>$request->user_id])->first();
            
            if(!empty($friendship_check)){
                 if($request->status == 'B')
                   {
                        $change_status = Friend::where(['user_id'=> $request->user_id, 'friend_id'=>$request->friend_id])->delete();
                         if($change_status == 1)
                               {
                                  return response()->json(['status'=>'successfully blocked']); 
                               }else
                               {
                                  return response()->json(['status'=>'failed']); 
                               }
                   }
            }elseif(!empty($reveerse_friendship_check))
            {
                if($request->status == 'B')
                   {
                        $change_status = Friend::where(['user_id'=> $request->friend_id, 'friend_id'=>$request->user_id])->delete();
                         if($change_status == 1)
                               {
                                  return response()->json(['status'=>'successfully blocked']); 
                               }else
                               {
                                  return response()->json(['status'=>'failed']); 
                               }
                   }
            }else
            {
                return response()->json(['success'=>true, 'message'=>'Unable to block!']);
            }
           
            
        }else
        {
            return response()->json(['success'=> false, 'message'=> 'Unable to block!']);
        }
        
   }
   
   
   public function add_chatFlag(Request $request,$id)
   {
       $user= User::where('id', $id)->update(['chat_status'=>$request->chat_status]);
       if($user == 1)
       {
           return response()->json(['success'=>true]);
       }else
       {
           return response()->json(['success'=> false]);
       }
   }
   
   public function chat_request_notification(Request $request)
   {
       $request->validate([
           'user_id'=>'required', 
           'friend_id'=> 'required', 
           'post_id'=> 'required',
           'type'=> 'required'
           
           ]);
           
           $post = Post::where('id', $request->post_id)->first();
           
           
           
           
           $user_detail = User::where('id', $request->user_id)->first();
           $user_name = $user_detail->name;
           $user_id = $user_detail->id;
           $user_image = $user_detail->image;
           $device_token = $user_detail->device_token;
               
           $friend_detail = User::where('id', $request->friend_id)->first();
           $friend_device_token = $friend_detail->device_token;
           $friend_name = $friend_detail->name;
           
           $notification= new Notification();
           $notification->sender_id = $request->user_id;
           $notification->receiver_id = $request->friend_id;
           $notification->post_id = $request->post_id;
           $notification->description ="يريد الدردشة معك".' '.$user_name;
           $notification->type = $request->type;
           $arabic_message = 'يريد الدردشة معك'; 
           
            $response_message = array(
                "message" => $arabic_message,
                "username" => $user_name
            );
            // return $response_message['message']. ' '.$response_message['username'];
           
   

if ($notification->save()) {
    $curl = curl_init();
    $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';
    $serverKey = 'AAAAgBMZwnU:APA91bES_J_LF43lziKcVIQXYFlPWhe5gP2Lzm51goG41z_6DJNjpLHZ8TF1tSddG-uyolRMGBV47zpESjaAiiDqL5m7zS_yzCrj5tSiPimBj6_HqhEeQ4QXf-kn8U6_apkPHPup8j2n';

    $payload = [
        'to' => $friend_device_token,
        'notification' => [
            'body' => $response_message['message']. ' '.$response_message['username'],
            'user_id' => $user_id,
            'user_image' => $user_image,
        ],
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: key=' . $serverKey,
    ];

    $curlOptions = [
        CURLOPT_URL => $fcmEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
    ];

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($httpCode === 200) {
        return response()->json(['success' => true, 'user' => $user_detail, 'type' => $request->type, 'response' => $response]);
    } else {
        // Handle error or log the response for debugging
        return response()->json(['success' => false, 'error' => 'FCM request failed', 'response' => $response]);
    }
} else {
    return response()->json(['success' => false]);
}
}
   
    public function bid_closed(Request $request)
    {
       $request->validate([
           
           'bid_status'=> 'required',
           'post_id' => 'required'
           
           ]);    
           
           $update_bid_status = Post::where('id', $request->post_id)->update(['bid_status'=> $request->bid_status]);
           if($update_bid_status == 1)
           {
                return response()->json(['success'=>true, 'message'=> 'تم إغلاق العطاء بنجاح']);
           }else
           {
                return response()->json(['success'=>false]);
           }
           
    }
    
    public function test()
    {
            $post = DB::select(DB::raw("SELECT posts.id as postId,posts.title as post_title,post_user.id as post_user_id, post_user.name as post_userr,post_user.device_token as post_user_device_token,bid_user.id as bid_user_id,bid_user.name as bid_user_name,bid_user.device_token as bid_user_device_token, DATEDIFF(CURDATE(), posts.created_at) AS CountDaysPassed 
                             FROM `posts` 
                             left join users as post_user on post_user.id = posts.user_id 

                             left join post_bids on post_bids.post_id = posts.id 
                             
                             left join users as bid_user on bid_user.id = post_bids.user_id
                             
                             WHERE posts.bid_status = 0
                             AND category_id IN (8,2,6)
                             AND price_type = 'سوم'
                             AND expired_days IS NOT NULL
                             AND expired_days > 0
                             AND DATEDIFF(CURDATE(), posts.created_at) = expired_days
                             GROUP BY posts.id"));

            foreach($post as $posts){
                
               $bid_user_id = $posts->bid_user_id;
               $postId = $posts->postId; 
               $post_user_id = $posts->post_user_id;
               $post_title = $posts->post_title;
               $post_userr = $posts->post_userr;
               $post_user_device_token = $posts->post_user_device_token;
               $bid_user_name = $posts->bid_user_name;
               $bid_user_device_token = $posts->bid_user_device_token;
               $CountDaysPassed = $posts->CountDaysPassed;
               
            //   Post::where('id', $posts->id)->update(['bid_status'=> 1]);

               

               if(!empty($post_userr) && !empty($post_user_device_token) && !empty($bid_user_name) && !empty($bid_user_device_token)){
                  
                  $notification = new Notification();
                  $notification->description = " '.$post_userr.' your post has been expired and is awarded to '.$bid_user_name.' ";
                  $notification->sender_id = 0;
                  $notification->receiver_id = $post_user_id;
                  $notification->post_id = $postId;
                  $notification->save();
                  
                  
                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$post_user_device_token.'",
                 "notification" : {
                     
                     "body": "'.$post_userr.' your post has been expired and is awarded to '.$bid_user_name.'"
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    // 'Authorization: key=AAAA_pGO44o:APA91bFhrkEoeEvP9Ukzw5QFnxb5UNPx7DOrrvA5ayJzFY6BsMF0oxkSZt6MveWwSldTiROUMSSsCTyk9ZKE27m2F34pIjuySC_SWR9LuE2G_7Q_Hv4TL7K0Ru77q2qmhAm9bX4DZHgI'
                   'Authorization: key='.$this->firebase_key.''
                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                echo $response;
                
                
                if(!empty($bid_user_name) && !empty($bid_user_device_token)){
                  
                  $notification = new Notification();
                  $notification->description = "Congratulations '.$bid_user_name.',  the bid has been awarded to you on '.$post_title.' post.";
                  $notification->sender_id = 0;
                  $notification->receiver_id = $bid_user_id;
                  $notification->post_id = $postId;
                  $notification->save();
                
                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$bid_user_device_token.'",
                 "notification" : {
                     
                     "body": "Congratulations '.$bid_user_name.',  the bid has been awarded to you on '.$post_title.' post."
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    // 'Authorization: key=AAAA_pGO44o:APA91bFhrkEoeEvP9Ukzw5QFnxb5UNPx7DOrrvA5ayJzFY6BsMF0oxkSZt6MveWwSldTiROUMSSsCTyk9ZKE27m2F34pIjuySC_SWR9LuE2G_7Q_Hv4TL7K0Ru77q2qmhAm9bX4DZHgI'
                    'Authorization: key='.$this->firebase_key.''
                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                echo $response;                  
                  
                   
               }
                                      
                  
                   
               }else{
                
                  $notification = new Notification();
                  $notification->description = "'.$post_userr.' your post has been expired.";
                  $notification->sender_id = 0;
                  $notification->receiver_id = $post_user_id;
                  $notification->post_id = $postId;
                  $notification->save();
                   
                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>'{
                 "to" : "'.$post_user_device_token.'",
                 "notification" : {
                     
                     "body": "'.$post_userr.' your post has been expired."
                 }
                }',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    // 'Authorization: key=AAAA_pGO44o:APA91bFhrkEoeEvP9Ukzw5QFnxb5UNPx7DOrrvA5ayJzFY6BsMF0oxkSZt6MveWwSldTiROUMSSsCTyk9ZKE27m2F34pIjuySC_SWR9LuE2G_7Q_Hv4TL7K0Ru77q2qmhAm9bX4DZHgI'
                    'Authorization: key='.$this->firebase_key.''
                  ),
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                echo $response;
                   
               }
           
               
              
               
                                   
            }         
            
            
    }
    
    
    public function change_password(Request $request)
    {
       $request->validate([
            'userid' => 'required',
            'currentpaswd' => 'required',
            'newpswd' => 'required',
            'confirmpswd' => 'required|same:newpswd',
        ]);

        $user = User::where('id', $request->userid)->first();

        if (!$user) {
            return response()->json(['message' => 'لم يتم العثور على المستخدم'], 404);
        }

        if (!Hash::check($request->currentpaswd, $user->password)) {
            return response()->json(['status'=>false,'message' => 'كلمة مرور غير صحيحة']);
        }
        if($request->currentpaswd === $request->newpswd){
            return response()->json(['status'=>false,'message' => 'لا يمكن أن تكون كلمة المرور الحالية وكلمة المرور الجديدة متماثلتين']);     }

        $user->password = bcrypt($request->newpswd);
        $user->save();

        return response()->json(['message' => 'تم تحديث كلمة السر بنجاح'], 200);
    }

    function getAllFollowers(Request $request,$id){
        $data = UserFollower::where('user_id',$id)->orderBy('id', 'desc')->get();
        if(count($data)<=0){
        return response()->json(['message' => 'لم يتم العثور على المتابعين'], 200);    
        }
        if(count($data)>0){
        $result=[];
        foreach($data as $item){
         $user=User::where('id',$item->follower_id)->first();
         $result[] = ['user' => $user, 'item' => $item];
        }
        return response()->json(['message' => 'قائمة المتابعين',$result], 200);
        }   
    }
    function getAllfollowing(Request $request,$id){
        $data = UserFollower::where('follower_id',$id)->orderBy('id', 'desc')->get();
        if(count($data)<=0){
        return response()->json(['message' => 'لا أحد يتابعك'], 200);    
        }
        
        if(count($data)>0){
        $result=[];
        foreach($data as $item){
         $user=User::where('id',$item->user_id)->first();
         $result[] = ['user' => $user, 'item' => $item];
        }
        return response()->json(['message' => 'قائمة المتابعين',$result], 200);
        }   
    }
    
}
   

