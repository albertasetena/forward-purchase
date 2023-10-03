jQuery(document).ready(function($) {
    // Toggle switch change event
    let originalToggleState = false; //
    $('#forward-purchase-toggle').prop('checked', originalToggleState);
    let isPopUpOpen = false;
    $('#forward-purchase-toggle').on('change', function() {
      originalToggleState = $(this).is(':checked');
      console.log('Toggle Switch Change Event Fired');
      if ($(this).is(':checked')) {
        let confirmation = confirm("Activating Forward Purchase Program for your current order");
        if (confirmation) {
          getCurrentOrderID(function(orderID) {
            if (orderID) {
              // Save the current order ID to local storage
              showPopUp(true);
              console.log("Gotten Order Id");
            } else {
              handleOrderIDError(orderID);
            }
          });
        } else {
          resetToggleState(); 
        }
      } else {
        alert('Forward Purchase Program deactivated');
        getCurrentOrderID(function(orderID) {
          if (orderID) {
            updateOrderStatus('0', orderID, false);
          } else {
            handleOrderIDError("txt1");
          }
        });
      }
    });
  
    function getCurrentOrderID(callback) {
      console.log(`Toggle Switch ${forward_purchase_ajax.ajax_url}`);
      $.ajax({
        url: forward_purchase_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'forward_purchase_get_current_order_id',
          security: forward_purchase_ajax.nonce,
          order_id: forward_purchase_ajax.order_id
        },
        success: function(response) {
          console.log('AJAX Request Successful:', response); 
          if (response.success) {
            console.log('Order ID:', response.data.order_id);
            callback(response.data.order_id);
          } else {
            console.log('Error:', response.message);
            handleOrderIDError("txt2");
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.log('AJAX Request Error:', errorThrown);
          handleOrderIDError(textStatus + " Manedem");
        }
      });
    }
  
  
  // Check the current order status
  function getCurrentOrderStatus(orderID, callback) {
    $.ajax({
      url: forward_purchase_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'forward_purchase_get_current_order_status',
        security: forward_purchase_ajax.nonce,
        order_id: orderID
      },
      success: function(response) {
        console.log('AJAX Request Successful:', response);
        if (response.success) {
          const orderStatus = response.data.order_status;
          const isForwardPurchased = orderStatus === 'forward-purchase';
          setToggleState(isForwardPurchased);
          callback(isForwardPurchased); 
        } else {
          console.log('Error:', response.message);
          handleOrderIDError("txt2");
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.log('AJAX Request Error:', errorThrown);
        handleOrderIDError(textStatus + " Manedem");
      }
    });
  }
  
    // Get the current order status
    getCurrentOrderID(function(orderID) {
      if (orderID) {
        getCurrentOrderStatus(orderID, function(isForwardPurchased) {
          setToggleState(isForwardPurchased);
        });
      } else {
        handleOrderIDError(orderID);
      }
    });
    
    // Update toggle state
    function setToggleState(newState) {
      $('#forward-purchase-toggle').prop('checked', newState);
    }

    function updateOrderStatus(status, orderID, reload) {
      console.log('Order ID :', orderID, ' to be activated');
      $.ajax({
        url: forward_purchase_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'forward_purchase_update_order_status',
          forward_purchase_activated: status === '1' ? '1' : '0',
          order_id: orderID,
          security: forward_purchase_ajax.nonce
        },
        success: function(response) {
          console.log('AJAX Request Successful ', response, ":", orderID);
          getCurrentOrderStatus(orderID, function(isForwardPurchased) {
            if (isForwardPurchased) {
              setToggleState(true);
              if (reload) {
                location.reload();
                // Delay the page reload by 1 second
                setTimeout(function() {
                  location.reload();
                }, 100);
              }
            }
          });
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.log('AJAX Request Error ', jqXHR.responseText);
        }
      });
    }
  
  // Form Submission and Program Activation
  $('#confirm-date-btn').on('click', function(event) {
    event.preventDefault();
    console.log("Confirmation Date Fired");
  
    // Get the order ID and expected pickup date
    const orderID = $('#orderIDPopUp').val();
    const orderDate = $('#orderDate').val(); 
    const expectedDate = $('#expDate').val();
    console.log("Order Details: ", orderID, "",orderDate, "", expectedDate);
  
    // Check if the order ID is valid (You may need to add your own validation logic here)
    if (!orderID) {
      alert('Please enter a valid order ID.');
      return;
    }
  
    // AJAX request to update the order status and save the data in the database
    console.log('Valid ID found: ', orderID + " ", orderDate + " ", expectedDate);
    $.ajax({
      url: forward_purchase_ajax.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'forward_purchase_handle_form_submission',
        security: forward_purchase_ajax.nonce,
        forward_purchase_submit: true,
        current_order_id: orderID,
        order_date: orderDate, 
        expected_pickup_date: expectedDate
      },
      success: function(response) {
        console.log('AJAX Request Successful: submission', response);
        if (response.success) {
            console.log('Form submission successful:', response.data.message);
            updateOrderStatus('1', orderID, true);
            alert('Forward Purchase Program activated');
            hidePopUpContainer(true);
            // Save the state of the toggle switch in local storage
            originalToggleState = true; 
            localStorage.setItem('forward-purchase-toggle', 'checked');
          } 
        else {
            console.log('Error:', response.message);
            alert('Forward Purchase Program activation failed. ' + response.data.message);
        }
    },    
      error: function(jqXHR, textStatus, errorThrown) {
        console.log('AJAX Request Error:', errorThrown);
        alert('Forward Purchase Program activation failed. 2');
        localStorage.setItem('forward-purchase-toggle', 'unchecked');
      }
    });
  });
  
  
    // Pop-up form
    function showPopUp(show) {
      let popupContainer = $('.pop-up-container');
      if (show) {
        $('#forward-purchase-toggle').prop('checked', originalToggleState);
        popupContainer.addClass('pop-up-container-on');
        isPopUpOpen = true;
      } else {
        popupContainer.removeClass('pop-up-container-on');
      }
    }
     
     // Hide the pop-up container
     function hidePopUpContainer(hide) {
      if(hide)
      {
        $('.pop-up-container').removeClass('pop-up-container-on');
        isPopUpOpen = false;
      }
      else
      {
        showPopUp(true)
      }
      return originalToggleState;
    }
  
    // Outside the pop-up container
    function isClickedOutsidePopUpContainer(target) {
      return !$(target).closest('.pop-up').length;
    }
  
    // Detect clicks on the document body
    $(document).on('click', function(e) {
      if (isClickedOutsidePopUpContainer(e.target) && isPopUpOpen) {
        hidePopUpContainer(true);
      }
    });
  

  });

 
  
