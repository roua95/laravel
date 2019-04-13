<?php

namespace App\Http\Controllers;

use App\Plan;
use APP\Category;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PlanController extends Controller
{
    static $totalRates;
    static $rateUsers = array();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        return Plan::All()->toArray();
        //$request->Plan()->authorizeCategory(['café', '']);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
        // return view('categories.create');

        $plan = Plan::create([

            'id' => $request->get('id'),
            'user_id' => $request->get('user_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'adresse' => $request->get('adresse'),
            'rate' => $request->get('rate'),
            'longitude' => $request->get('longitude'),
            'lattitude' => $request->get('lattitude'),
            'ApprovedBy' => $request->get('ApprovedBy'),


        ]);


        $plan
            ->category()
            ->attach(category::where('category_name', 'café')->first());

        //  return response()->json(compact([$category
        /* store(Request::$request);
             $category= new Category;
             $category->category_name=$request->get('category_name');
             $category->id=$request->get('id');*/

        $plan->save();

        return response()->json(compact('plan'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $plan = Plan::find($request->get('id'));
        return response()->json(compact('plan'));


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Plan $plan)
    {
        $plan = Plan::find($request->get('id'));
        $plan->description = $request->get('description');

        $plan->title = $request->get('title');

        if ($request->get('user_id') != "") {
            $plan->user_id = $request->get('user_id');
        }
        if ($request->get('longitude') != "") {
            $plan->longitude = $request->get('longitude');
            if ($request->get('lattitude') != "") {
                $plan->lattitude = $request->get('lattitude');
            }
            if ($request->get('region') != "") {
                $plan->region = $request->get('region');
            }

            if ($request->get('adresse') != "") {
                $plan->adresse = $request->get('adresse');
            }
            if ($request->get('rate') != "") {
                $plan->rate = $request->get('rate');
            }


            $plan->save();

        }
        return response()->json(compact('plan'));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, Plan $plan)
    {
        $plan = Plan::find($request->get('id'));
        $plan->delete();
        //

    }

    public function approve(Request $request, Plan $plan)
    {
        $plan = Plan::find($request->get('id'));
        $user = User::find($request->get('user_id'));
        if ($user->role == "admin") {
            if ($plan->approvedBy != "") {    //how to insert admin logged in id (mobile part ??)
                $plan->approvedBy = $request->get('user_id');
                return "Plan successfully approved";
            } else return "Plan already approved";

        } else return "you should be an admin to approve a plan";
    }

    public function showAllApprovedPlans(Request $request)
    {
        $plan = DB::table(Plan::all());
        $approvedPlan = Plan::find(where($plan->approvedBy) != "");
        return $approvedPlan->toArray();

    }

    public function showAllNotApprovedPlans(Request $request)
    {
        $plan = DB::table(Plan::all());

        $plan = Plan::find(where($plan->approvedBy == ""));
        return $plan->toArray();

    }

    public function showAllApprovedPlansByUserId(Request $request, Plan $plan)
    {
        $plans = Plan::find(where($request->get('userId') == $plan->approvedBy));
        return $plans->toArray();

    }

    public function like(Request $request, Plan $plan, User $user)
    {

    }
    ///total of likes attribute updated in plan table ????!!!
    ///
    public function unlike(Request $request, Plan $plan, User $user)
    {

    }

    public function getFavoritePlans(Request $request)
    {
        $ratedPlansByUser = DB::table('ratings')->get()->where('user_id', $request->get('user_id'));
        return $ratedPlansByUser->where($ratedPlansByUser->rating >= $request->min);

    }

    public function getRecommandedPlans(Request $request)
    {
        $ratedPlansByUser = DB::table('ratings')->get();

        $mostRatedPlansByUser = DB::table('ratings')->get()->where(($ratedPlansByUser->rating >= $request->min));
        return $mostRatedPlansByUser->toArray();

    }


    public function ratePlan(Request $request)

    {

        request()->validate(['rate' => 'required']);

        $plan = Plan::find($request->id);

        $rating = new \willvincent\Rateable\Rating;

        $rating->rating = $request->rate;

        // $rating->user_id = auth()->user()->id;

        $rating->user_id = $request->get('user_id');
        $requete = DB::table('ratings')->select('id')->where('user_id', $request->get('user_id'))->where('rateable_id', $request->get('id'))->first();
        if ($requete == null) {
            $plan->ratings()->save($rating);
            //$plan->rate = ((integer)$plan->rate + $rating->rating)/((integer)self::$totalRates);
            $totalRates = DB::table('ratings')->count();
            $sum = DB::table('ratings')->sum('rating');


            // $plan->rate = ((integer)$plan->rate + $rating->rating)/((integer)$totalRates);
            $plan->rate = $sum / $totalRates;
            //return $requete;
            $plan->save();
        } else return ("you already rated this plan !");

    }

////////////////////////////if user changes his mind about a plan !!
    public function changeRating(Request $request)
    {
        request()->validate(['rate' => 'required']);

        $plan = Plan::find($request->id);

        $rating = new \willvincent\Rateable\Rating;

        $rating->rating = $request->rate;

        // $rating->user_id = auth()->user()->id;

        $rating->user_id = $request->get('user_id');
        //$requete = DB::table('ratings')->select('id')->where('user_id', $request->get('user_id'))->where('rateable_id', $request->get('id'))->first();
        $sum = DB::table('ratings')->sum('rating');
        $totalRates = DB::table('ratings')->count();
        echo "avant" . $sum;
        $sum = $sum - $plan->rate;
        echo "après" . $sum;

        $sum = $sum + $request->rate;
        echo "après update" . $sum;
        $plan->rate = $sum / $totalRates;
        $plan->save();
        // $requete = DB::table('ratings')->get()->where('user_id', $request->get('user_id'))->where('rateable_id', $request->get('id'));
        $requete = DB::table('ratings')->get()->where('id', 'id')->where('user_id', 'user_id')->first();
        echo $requete;

        if ($requete != null)
            //$requete->update("rating",$request->rate);
            $requete->rating = $request->rate;
        echo $requete;
    }

    public function addTofavorites(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        $plan->addFavorite($request->get('user_id'));
        return $plan;
    }

    public function removeFromFavorites(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));

        $plan->removeFavorite($request->get('user_id'));
    }

    public function favoriteCount(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        return $plan->favoritesCount;
    }

    public function whoFavoritePlan(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        return $plan->favoritedBy();
    }

    public function isFavorited(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));

        return $plan->isFavorited($request->get('user_id'));
    }


    /////////liking stuff
    public function likesNumber(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));

        return $plan->likesCount;
    }

    public function dislikesNumber(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));

        return $plan->dislikesCount;
    }

    public function likePlan(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        $user = User::find($request->get('user_id'));

        $user->like($plan);
    }

    public function dislikePlan(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        $user = User::find($request->get('user_id'));

        $user->dislike($plan);
    }

    public function likedBy(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        $user = User::find($request->get('user_id'));
        if ($user->likedBy() == true) return "true";
        else return "false";
    }

    public function unlikedBy(Request $request)
    {
        $plan = Plan::find($request->get('plan_id'));
        $user = User::find($request->get('user_id'));
        if ($user->unlikedBy() == true) return "true";
        else return "false";
    }

    public function getAllPlansLikedByUser(Request $request)
    {
        return Plan::whereLikedBy($request->get('user_id'))
            ->with('likesCounter')// Allow eager load (optional)
            ->get()->toArray();
    }

    public function mostLikedPlans(Request $request)
    {
        return $recommandedPlans = Plan::orderByLikesCount()->get()->toArray();
    }

    public function shareOnFacebook(Request $request)
    {
        return Share::page('http://jorenvanhocht.be')->facebook();
    }

    public function shareOnTwitter(Request $request)
    {
       return Share::page('http://jorenvanhocht.be', 'Your share text can be placed here')->twitter();
    }
    public function shareOnGooglePlus(Request $request){
       return Share::page('http://jorenvanhocht.be')->googlePlus();
    }
public function shareCurrentPage(Request $request){
    return Share::currentPage()->facebook();
}
    public function shareMultiple(Request $request){
      return  Share::page('http://jorenvanhocht.be', 'Share title')
            ->facebook()
            ->twitter()
            ->googlePlus()
            ->linkedin('Extra linkedin summary can be passed here')
            ->whatsapp();
    }

}