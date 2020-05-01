// Datepicker
$( function() {
  $( ".datepicker" ).datepicker({
    dateFormat: "yy-mm-dd",
    changeMonth: true,
    changeYear: true,
    yearRange: "-100:+0",
  });
} );
$('body').on('change','#kd_dokter', function() {
     var optionText = $("#kd_dokter option:selected").text();
     $('#nm_dokter').val(optionText);
});
