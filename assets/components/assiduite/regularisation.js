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
  $("#excel_date").on("click", function () {
    var file = $('#formFileLg').val();
    // var urls = "\\\\172.20.0.54\\uiass\\regularisation\\dateseance\\";
    var urls = "\C:\\Users\\Administrateur\\Downloads\\";
     str = file.replace("C:\\fakepath\\", urls);
    //  alert(file);  
           url = "/api/regularisation_date?file="+str;
    //  url = url.replace("assiduite/assiduites/", '');  
  
  
           window.open(url);
             
  
              }); 
  
    $("#excel_seance").on("click", function () {
    var file = $('#formFileLg').val();
    // var urls = "\\\\172.20.0.54\\uiass\\regularisation\\dateseance\\";
    var urls = "\C:\\Users\\Administrateur\\Downloads\\";
     str = file.replace("C:\\fakepath\\", urls);
    //  alert(file);  
           url = "/api/regularisation_seance?file="+str;
    //  url = url.replace("assiduite/assiduites/", '');  
  
  
           window.open(url);
             
  
              });  