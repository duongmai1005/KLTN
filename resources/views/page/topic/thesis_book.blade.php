@extends('page.layouts.main')
@section('title')
@section('content')
    <div class="header bg-primary pb-6">
        <div class="container-fluid">
            <div class="header-body">
            @include('page.common.breadcrumb')
            <!-- Card stats -->
                @include('page.common.topic_user')
            </div>
        </div>
    </div>
    <div class="container-fluid mt--6">
        <div class="row">
            <div class="col-xl-12">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col" style="flex-basis: unset !important;">
                            <h3 class="mb-0" style="text-transform: uppercase;">NỘP QUYỂN KHÓA LUẬN : {{ isset($studentTopic->topic) ? $studentTopic->topic->topic->t_title : '' }}</h3>
                        </div>
                        <div class="col text-right">
                            {{--<a href="#!" class="btn btn-sm btn-primary">See all</a>--}}
                        </div>
                    </div>
                    <div class="card-body">
                        <form role="form" action="{{ route('user.post.thesis.book', $studentTopic->id) }}" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label" for="input-username" style="float: left;">Tên quyển khóa luận <sup class="title-sup">(*)</sup></label>
                                        <input type="text" id="input-username" class="form-control" name="st_thesis_book" value="{{ old('st_thesis_book') }}">
                                        <span class="text-danger"><p class="mg-t-5">{{ $errors->first('st_thesis_book') }}</p></span>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label" for="input-first-name" style="float: left;">File đính kèm <sup class="title-sup">(*)</sup></label>
                                        <input type="file" id="input-first-name" name="thesis_book" class="form-control">
                                        <span class="text-danger"><p class="mg-t-5">{{ $errors->first('thesis_book') }}</p></span>
                                    </div>
                                </div>
                            </div>
                            @csrf
                            @if (isset($studentTopic->topic) && checkInTime($studentTopic->topic->tc_start_thesis_book, $studentTopic->topic->tc_end_thesis_book))
                                <div style="text-align: right">
                                    <button type="submit" class="btn btn-primary text-right"> Nộp báo cáo</button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer -->
        @include('page.common.footer')
    </div>
@stop