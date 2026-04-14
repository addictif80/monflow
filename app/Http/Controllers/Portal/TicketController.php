<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\{Ticket, TicketMessage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index() { return view('tickets.list', ['tickets' => Ticket::where('user_id', Auth::id())->latest()->get()]); }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['subject' => 'required|max:255', 'category' => 'required', 'priority' => 'required', 'message' => 'required']);
            $ticket = Ticket::create(['user_id' => Auth::id(), 'subject' => $data['subject'], 'category' => $data['category'], 'priority' => $data['priority']]);
            TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => Auth::id(), 'body' => $data['message']]);
            return redirect("/support/tickets/{$ticket->id}")->with('success', 'Ticket créé.');
        }
        return view('tickets.create');
    }

    public function show(string $id, Request $request)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);
        if ($request->isMethod('post') && $request->body) {
            TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => Auth::id(), 'body' => $request->body]);
            if ($ticket->status === 'waiting_customer') $ticket->update(['status' => 'in_progress']);
            return back()->with('success', 'Message envoyé.');
        }
        return view('tickets.detail', ['ticket' => $ticket, 'messages' => $ticket->messages()->with('author')->get()]);
    }
}
