jQuery(function ($) {
    $("#main-menu-navigation li a").click(function(e) {
            var link = $(this);
            var item = link.parent("li");   
            if (item.hasClass("active")) {
                item.removeClass("active");    
            } else {
                item.addClass("active");
            }
            if (item.children("li").length > 0) {
                var href = link.attr("href");
                link.attr("href", "#");
                setTimeout(function () { 
                    link.attr("href", href);
                });
                e.preventDefault();
            }
        })
        .each(function() {
            var link = $(this);
            if (link.get(0).href === location.href) {
                link.addClass("active").parents("li").addClass("active");
                return false;
            }
        });
     });
//==========================================language=======================

function setCookie(name,value,days){
  var expires = "";
  if (days) {
      var date = new Date();
      date.setTime(date.getTime() + (days*24*60*60*1000));
      expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

$(document).on('click', ".language a", function (){
  $('#lang_selected').text($(this).text());
  $('#lang_selected').attr("data-code",$(this).attr("data-code"));
  var path = "", urlWithOutParameters="", params= "";
  if(window.location.search != ""){
      const urlParams = new URLSearchParams(window.location.search);
      if(urlParams.get('lang')) urlParams.delete('lang');
      urlWithOutParameters =  window.location.origin + window.location.pathname;
      if(urlParams.toString() == ""){ params = "";}else{params= "&"+urlParams.toString()}
      path = '?lang='+$(this).attr("data-code")+params;
  }else{
      urlWithOutParameters =  window.location.origin + window.location.pathname;
      path = '?lang='+$(this).attr("data-code");
  }
    window.location = urlWithOutParameters+path;
    setCookie("lang", $(this).attr("data-code"), 30);
        location.reload();
 
  
});

    $('#forget_password').on('submit',function(e){
        e.preventDefault();
        $.ajax({
            url: SITE_URL + "/handlers",
              method:"POST",
              data:{method:'forget_password', email:$('#forgot_email').val()},
              success:function(data){
				 
				 Swal.fire({
                        title: 'Change successfully',
                        icon: 'success',
                        confirmButtonText: 'back',
                        customClass: {
                        confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                           window.location.href = SITE_URL + "/login";
                        }
                        })
            }
            
        });
    }); 


     

