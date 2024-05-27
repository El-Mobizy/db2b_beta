







<form class="row g-3" method="POST" action="{{route('login')}}">
    @csrf
  <div class="col-auto">
    <label for="staticEmail2" class="visually-hidden">Email</label>

    <input type="text" class="form-control" name="username" placeholder="email">
  </div>

  <div class="col-auto">
    <label for="inputPassword2" class="visually-hidden">Password</label>
    <input type="password" class="form-control" name="password" placeholder="Password">
  </div>

  <div class="col-auto">
    <button type="submit" class="btn btn-primary mb-3">Confirm identity</button>
  </div>
</form>