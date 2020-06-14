// Datepicker
$( function() {
  $( ".datepicker" ).datepicker({
    dateFormat: "yy-mm-dd",
    changeMonth: true,
    changeYear: true,
    yearRange: "-100:+0",
  });
} );

$( function() {
  $('.timepicker').timepicker();
} );
$(document).ready(function(){
    $('.display').DataTable({
      "lengthChange": false,
      "scrollX": true      
    });
});
$(document).ready(function(){
    var keyword = '';
    load_data(keyword);
    function load_data(keyword) {
      $.ajax({
        type: 'GET',
        url: '{?=url(ADMIN)?}/pendaftaran/ajax?keyword='+keyword+'&t={?=$_SESSION['token']?}',
        success: function(response) {
          $('#pasien').html(response);
        }
      })
    }
    $('#s_keyword').keyup(function(){
  		var keyword = $("#s_keyword").val();
			load_data(keyword);
		});
});

$(document).on('click', '.pilihpasien', function (e) {
    $("#no_rkm_medis")[0].value = $(this).attr('data-norkmmedis');
    $("#nm_pasien")[0].value = $(this).attr('data-nmpasien');
    $("#namakeluarga")[0].value = $(this).attr('data-namakeluarga');
    $("#alamatkeluarga")[0].value = $(this).attr('data-alamatkeluarga');
    $('#pasienModal').modal('hide');
});
