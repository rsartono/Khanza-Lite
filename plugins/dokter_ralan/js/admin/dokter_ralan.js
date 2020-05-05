$(document).ready(function() {
    if (location.hash) {
        $("a[href='" + location.hash + "']").tab("show");
    }
    $(document.body).on("click", "a[data-toggle='tab']", function(event) {
        location.hash = this.getAttribute("href");
    });
});
$(window).on("popstate", function() {
    var anchor = location.hash || $("a[data-toggle='tab']").first().attr("href");
    $("a[href='" + anchor + "']").tab("show");
});

$(document).ready(function () {
  var strip_tags = function(str) {
    return (str + '').replace(/<\/?[^>]+(>|$)/g, '')
  };
  var truncate_string = function(str, chars) {
    if ($.trim(str).length <= chars) {
      return str;
    } else {
      return $.trim(str.substr(0, chars)) + 'â€¦';
    }
  };
  $('select').selectator('destroy');
  $('.databarang_ajax').selectator({
    labels: {
      search: 'Cari obat...'
    },
    load: function (search, callback) {
      if (search.length < this.minSearchLength) return callback();
      $.ajax({
        url: '{?=url()?}/admin/dokter_ralan/ajax?show=databarang&nama_brng=' + encodeURIComponent(search) + '&t={?=$_SESSION['token']?}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          callback(data.slice(0, 100));
          console.log(data);
        },
        error: function() {
          callback();
        }
      });
    },
    delay: 300,
    minSearchLength: 1,
    valueField: 'kode_brng',
    textField: 'nama_brng'
  });
  $('.jns_perawatan_lab_ajax').selectator({
    labels: {
      search: 'Cari perawatan lab...'
    },
    load: function (search, callback) {
      if (search.length < this.minSearchLength) return callback();
      $.ajax({
        url: '{?=url()?}/admin/dokter_ralan/ajax?show=jns_perawatan_lab&nm_perawatan=' + encodeURIComponent(search) + '&t={?=$_SESSION['token']?}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          callback(data.slice(0, 100));
          console.log(data);
        },
        error: function() {
          callback();
        }
      });
    },
    delay: 300,
    minSearchLength: 1,
    valueField: 'kd_jenis_prw',
    textField: 'nm_perawatan'
  });
  $('.jns_perawatan_rad_ajax').selectator({
    labels: {
      search: 'Cari perawatan radiologi...'
    },
    load: function (search, callback) {
      if (search.length < this.minSearchLength) return callback();
      $.ajax({
        url: '{?=url()?}/admin/dokter_ralan/ajax?show=jns_perawatan_radiologi&nm_perawatan=' + encodeURIComponent(search) + '&t={?=$_SESSION['token']?}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          callback(data.slice(0, 100));
          console.log(data);
        },
        error: function() {
          callback();
        }
      });
    },
    delay: 300,
    minSearchLength: 1,
    valueField: 'kd_jenis_prw',
    textField: 'nm_perawatan'
  });
  $('select').selectator();
});
