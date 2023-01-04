@extends('layout.index')

@section('content')
  <div class="wrapper-content mt-3">
    @include('layout.messages')
    <form method="post" class="row">
      @csrf
        <div class="col-lg-6 col-sx-12 col-md-6">
          <div class="form-group">
            <label for="">Lựa chọn nền tảng</label>
            <select name="basis" id="select_basis" class="form-control">
              <option value="1" @selected(old('basis') == 1)>nhachay360</option>
            </select>
          </div>
        </div>
        <div class="col-lg-6 col-sx-12 col-md-6">
          <div class="form-group">
            <label for="">Lựa chọn loại</label>
            <select name="type" id="select_type" class="form-control">
              <option value="1" @selected(old('type') == 1)>Only</option>
              <option value="2" @selected(old('type') == 2)>All</option>
            </select>
          </div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label for="exampleInputEmail1">Nhập domain</label>
            <input type="url" class="form-control" name="domain" value="{{ old('domain') }}" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="http://google.com...">
          </div>
        </div>
        <div class="col-12">
          <div class="text-center">
            <button type="submit" class="btn btn-primary" formaction="{{ route('post-crawl-data') }}">Crawl Data</button>
          </div>
        </div>
    </form>
  </div>
@endsection
