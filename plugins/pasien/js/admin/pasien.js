// Datepicker
$( function() {
  $( ".datepicker" ).datepicker({
    dateFormat: "yy-mm-dd",
    changeMonth: true,
    changeYear: true,
    yearRange: "-100:+0",
  });
} );

$(document).ready(function(){
    $.ajax({
      type: 'GET',
      url: '{?=url()?}/admin/pasien/ajax?show=propinsi&t={?=$_SESSION[token]?}',
      success: function(response) {
        $('#propinsi').html(response);
        console.log(response);
      }
    })
});

$(document).on('click', '.pilihpropinsi', function (e) {
  $("#kd_prop")[0].value = $(this).attr('data-kdprop');
  $("#namaprop")[0].value = $(this).attr('data-namaprop');
  $('#propinsiModal').modal('hide');
  var kd_prop = $(this).attr('data-kdprop');
  $.ajax({
    type: 'GET',
    url: '{?=url()?}/admin/pasien/ajax?show=kabupaten&kd_prop='+kd_prop+'&t={?=$_SESSION[token]?}',
    success: function(response) {
      $('#kabupaten').html(response);
      console.log(kd_prop);
    }
  })
});

$(document).on('click', '.pilihkabupaten', function (e) {
  $("#kd_kab")[0].value = $(this).attr('data-kdkab');
  $("#namakab")[0].value = $(this).attr('data-namakab');
  $('#kabupatenModal').modal('hide');
  var kd_kab = $(this).attr('data-kdkab');
  $.ajax({
    type: 'GET',
    url: '{?=url()?}/admin/pasien/ajax?show=kecamatan&kd_kab='+kd_kab+'&t={?=$_SESSION[token]?}',
    success: function(response) {
      $('#kecamatan').html(response);
      console.log(response);
    }
  })
});

$(document).on('click', '.pilihkecamatan', function (e) {
  $("#kd_kec")[0].value = $(this).attr('data-kdkec');
  $("#namakec")[0].value = $(this).attr('data-namakec');
  $('#kecamatanModal').modal('hide');
  var kd_kec = $(this).attr('data-kdkec');
  $.ajax({
    type: 'GET',
    url: '{?=url()?}/admin/pasien/ajax?show=kelurahan&kd_kec='+kd_kec+'&t={?=$_SESSION[token]?}',
    success: function(response) {
      $('#kelurahan').html(response);
      console.log(response);
    }
  })
});

$(document).on('click', '.pilihkelurahan', function (e) {
    $("#kd_kel")[0].value = $(this).attr('data-kdkel');
    $("#namakel")[0].value = $(this).attr('data-namakel');
    $('#kelurahanModal').modal('hide');
});

$("#copy_alamat").click(function(){
    $("#alamatpj")[0].value = $("#alamat").val();
    $("#propinsipj")[0].value = $("#namaprop").val();
    $("#kabupatenpj")[0].value = $("#namakab").val();
    $("#kecamatanpj")[0].value = $("#namakec").val();
    $("#kelurahanpj")[0].value = $("#namakel").val();
});
