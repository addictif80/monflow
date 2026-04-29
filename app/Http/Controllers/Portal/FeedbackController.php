<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index()
    {
        return view('portal.feedback.list', [
            'feedbacks' => Feedback::where('user_id', Auth::id())->latest()->get(),
        ]);
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'type' => 'required|in:bug,suggestion,ui,performance,other',
                'subject' => 'required|max:255',
                'body' => 'required|max:5000',
            ]);
            Feedback::create([
                'user_id' => Auth::id(),
                'type' => $data['type'],
                'subject' => $data['subject'],
                'body' => $data['body'],
            ]);
            return redirect('/portal/feedback')->with('success', 'Merci pour votre retour !');
        }
        return view('portal.feedback.create');
    }

    public function show(string $id)
    {
        $feedback = Feedback::where('user_id', Auth::id())->findOrFail($id);
        return view('portal.feedback.show', ['feedback' => $feedback]);
    }
}
