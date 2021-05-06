<?php

namespace App\Http\Controllers;

use App\Contact;
use App\ContactNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ContactRequest;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contacts = Contact::get();

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ContactRequest $request)
    {
        DB::beginTransaction();
        try {
            $contact = null;
            $contact_numbers = [];

            $contact = Contact::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name]
            );

            if ($request->id) {
                $existing_contact_number = $contact->contactNumbers->pluck('number')->toArray();
                $updated_contact_numbers = collect($request->get('contact_numbers'))->pluck('number')->toArray();
                $deleted_contact_numbers = array_diff($existing_contact_number, $updated_contact_numbers);

                ContactNumber::where('number', $deleted_contact_numbers)->delete();

                foreach($request->get('contact_numbers') as $contact_number){
                    $contact_numbers[] = ContactNumber::updateOrCreate(
                        [
                            'contact_id' => $contact->id,
                            'number' => $contact_number['number']
                        ],
                        [
                            'number' => $contact_number['number']
                        ],
                    );
                }
            } else {
                $contact_numbers = $contact->contactNumbers()->createMany($request->get('contact_numbers'));
            }

            DB::commit();
            return response()->json(['contact' => $contact, 'contact_numbers' => $contact_numbers]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return Contact::with('contactNumbers')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Contact::find($id)->delete();
     
        return response()->json(['success'=>'Contact deleted successfully.']);
    }
}
