@extends('layout.index')

@section('content')
  <div class="wrapper-content mt-3">
    @include('layout.messages')
    <form method="post" class="row">
      @csrf
        <div class="col-lg-6 col-sx-12 col-md-6">
          <div class="form-group">
            <label for="">Lựa chọn nền tảng</label>
            <select name="basis" id="" class="form-control">
              <option value="1" @selected(old('basis') == 1)>Nền tảng Shoplaza</option>
              <option value="2" @selected(old('basis') == 2)>Nền tảng Cloudfront</option>
            </select>
          </div>
        </div>
        <div class="col-lg-6 col-sx-12 col-md-6">
          <div class="form-group">
            <label for="">Lựa chọn loại</label>
            <select name="type" id="" class="form-control">
              <option value="1" @selected(old('type') == 1)>One Product</option>
              <option value="2" @selected(old('type') == 2)>Collection</option>
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
            <button type="submit" class="btn btn-primary" formaction="{{ route('post-crawl-data') }}">Xuất Excel</button>
            <button type="submit" class="btn btn-primary ml-2" formaction="{{ route('post-view-data') }}">Xem Nội Dung</button>
          </div>
        </div>
    </form>
    @if (Session::has('data'))
      <div style="padding-bottom: 200px;" style="mt-3">
        @php
          $dataRoot = Session::get('data');
          $headers = $dataRoot[0];
          unset($dataRoot[0]);
          $data = $dataRoot;
        @endphp
        <h3>List Data</h3>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                @foreach ($headers as $item)
                  <th scope="col">{{ $item }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach ($data as $row)
                <tr>
                  @foreach ($row as $key => $value)
                    <td style="vertical-align: middle !important;">
                      <div class="row-content" style="width: 160px !important;">
                        @if ($key == "description" )
                          <button type="button" class="btn btn-primary btn-sm" onclick="showElement(`{{ $value }}`)">Xem mô tả</button>
                        @else
                          {{ $value }}
                        @endif
                      </div>
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>

  <!-- Modal -->
  <div class="modal fade" id="showElement" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Mô tả sản phẩm</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="contentElement">

        </div>
      </div>
    </div>
  </div>
@endsection
@section('script')
  <script>
      function showElement(element) {
        $("#showElement").modal("show");
        $("#contentElement").html(element);
      }
  </script>
@endsection
