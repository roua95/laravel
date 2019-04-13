<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Like;
class LikeController extends Controller
{
    //

    public function likePlan($id)
    {
        // here you can check if product exists or is valid or whatever

        $this->handleLike('App\Plan', $id);
        return redirect()->back();
    }

    public function handleLike( $id)
    {
        $existing_like = Like::All()->whereLikeableId($id)->whereUserId(Auth::id())->first();

        if (is_null($existing_like)) {
            Like::create([
                'user_id' => Auth::id(),
                'plan_id' => $id,
            ]);
        } else {
            if (is_null($existing_like->deleted_at)) {
                $existing_like->delete();
            } else {
                $existing_like->restore();
            }
        }
    }
}