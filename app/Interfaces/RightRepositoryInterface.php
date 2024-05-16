<?php

namespace App\Interfaces;
use Illuminate\Http\Request;

interface RightRepositoryInterface
{
    public function getAll();
    public function store(Request $request);
    public function update(Request $request, $id);
    public function destroy($id);
}
