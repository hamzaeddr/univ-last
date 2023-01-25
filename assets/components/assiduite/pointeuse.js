//////////////////////////////////////////////pointeuse--Interface////////////////////////////////////
const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
  });
  ////////////////////////////////////////////////////////////////////:

  $("#salle_pointeuse").on("change", function () {
    var salle = $(this).val();
    $(".loader").show();
  
    $.ajax({
      type: "POST",
      url: "/api/pointeuse_aff/" + salle,
      success: function (html) {
    $(".loader").hide();
  
        if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse")) {
          $("#dtDynamicVerticalScrollExample_pointeuse").DataTable().clear().destroy();
        }
        $("#dtDynamicVerticalScrollExample_pointeuse")
          .html(html)
          .DataTable({
            bLengthChange: false,
            lengthMenu: [
              [11, 25, 35, 50, 100, 20000000000000],
              [10, 15, 25, 50, 100, "All"],
            ],
            "font-size": "3rem",
          });
  
     
      }
    });
  });
    ////////////////////////////////////////////////////////////////////
  
    $("body #dtDynamicVerticalScrollExample_pointeuse").on("click", "tr", function () {
      var selected = $(this).hasClass("highlighty");
      $("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("highlighty");
      $("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("odd");
      $("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("even");
  
      if (!selected) {
        $(this).addClass("highlighty");
        var currentRow = $(this).closest("tr");
        list_pointeuse = [];
        list_pointeuse.push({
          sn: currentRow.find("td:eq(1)").html(),
          ip: currentRow.find("td:eq(2)").html(),
        });
       
       
      
      }
  
  
    });
  
    $("body #connect_pointeuse").on("click", function () {
      list_pointeuse.forEach((obj) => {
   $.ajax({
    type: "POST",
    url: "/api/pointeuse_connect/" + obj.ip,
    success: function (html) {
      
      if(html == 'true'){
        Toast.fire({
          icon: 'success',
          title: 'Pointeuse connected',
      })
  
      }
      else{
        Toast.fire({
          icon: 'error',
          title: 'pointeuse not connected',
      })
  
  
  
      }
  
    }
      });
    });
  
  });
  ///////////////att_pointeuse//////////

  $("#att_pointeuse").on("click", function () {
    var date = $("#datetime_pointeuse").val();
    list_pointeuse.forEach((obj) => {
      $.ajax({
        type: "POST",
        url: "/api/pointeuse_att/" + obj.ip + "/" + date ,
        success: function (html) {
          if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse2")) {
            $("#dtDynamicVerticalScrollExample_pointeuse2").DataTable().clear().destroy();
          }
          $("#dtDynamicVerticalScrollExample_pointeuse2")
            .html(html)
            .DataTable({
              bLengthChange: false,
              lengthMenu: [
                [11, 25, 35, 50, 100, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
              ],
              "font-size": "3rem",
            });
            
         
  
        },
      });
    });

  });

  ///////////////user_pointeuse//////////

  $("#user_pointeuse").on("click", function () {
    list_pointeuse.forEach((obj) => {
      $.ajax({
        type: "POST",
        url: "/api/pointeuse_user/" + obj.ip,
        success: function (html) {
          
          if ($.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse2")) {
            $("#dtDynamicVerticalScrollExample_pointeuse2").DataTable().clear().destroy();
          }
          $("#dtDynamicVerticalScrollExample_pointeuse2")
            .html(html)
            .DataTable({
              bLengthChange: false,
              lengthMenu: [
                [11, 25, 35, 50, 100, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
              ],
              "font-size": "3rem",
            });
            
         
  
        },
      });
    });

  });
  ///////////////download_pointeuse//////////

  $("#download_pointeuse").on("click", function () {
    var date = $("#datetime_pointeuse").val();
    list_pointeuse.forEach((obj) => {
      $.ajax({
        type: "POST",
        url: "/api/pointeuse_download/" + obj.ip + "/" + date,
        success: function (html) {
          
          Toast.fire({
            icon: 'success',
            title: 'Pointeuse connected',
        })
            
         
  
        },
      });
    });

  });