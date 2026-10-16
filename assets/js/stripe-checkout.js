/**
 * Stripe Checkout JavaScript
 */
(function () {
  'use strict';

  const stripe = window.stripe;
  const clientSecret = window.clientSecret;
  const orderRef = window.orderRef;

  if (!stripe || !clientSecret) {
    console.error('Stripe not initialized');
    return;
  }

  // Initialize Stripe Elements
  const elements = stripe.elements({
    clientSecret: clientSecret,
    appearance: {
      theme: 'night',
      variables: {
        colorPrimary: '#c9a227',
        colorBackground: '#121216',
        colorText: '#f4f2ef',
        colorDanger: '#f87171',
        fontFamily: 'Outfit, system-ui, sans-serif',
        spacingUnit: '8px',
        borderRadius: '8px',
      },
    },
  });

  // Create and mount card element
  const cardElement = elements.create('card', {
    style: {
      base: {
        iconColor: '#c9a227',
        color: '#f4f2ef',
        fontWeight: '500',
        fontFamily: 'Outfit, system-ui, sans-serif',
        fontSize: '16px',
        '::placeholder': {
          color: '#5c5854',
        },
      },
    },
  });

  cardElement.mount('#card-element');

  // Handle real-time validation errors
  cardElement.on('change', function (event) {
    const displayError = document.getElementById('card-errors');
    displayError.textContent = event.error ? event.error.message : '';
  });

  // Handle form submission
  const form = document.getElementById('payment-form');
  const submitButton = document.getElementById('submit-button');
  const buttonText = document.getElementById('button-text');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    setLoading(true);

    const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card: cardElement,
        billing_details: {
          name: '',
        },
      },
      return_url: window.location.origin + '/stripe-success.php?order_ref=' + encodeURIComponent(orderRef),
    }, {
      handleActions: false,
    });

    if (error) {
      if (error.type === 'card_error' || error.type === 'validation_error') {
        document.getElementById('card-errors').textContent = error.message;
      } else {
        document.getElementById('card-errors').textContent = 'An unexpected error occurred. Please try again.';
      }
      setLoading(false);
    } else if (paymentIntent) {
      if (paymentIntent.status === 'requires_action') {
        // Card requires authentication (3D Secure)
        stripe.confirmCardPayment(clientSecret, {
          payment_method: {
            card: cardElement,
            billing_details: {
              name: '',
            },
          },
        }).then(function(result) {
          if (result.error) {
            document.getElementById('card-errors').textContent = result.error.message;
            setLoading(false);
          } else {
            // Payment succeeded, will redirect via return_url
          }
        });
      } else if (paymentIntent.status === 'succeeded') {
        // Payment already succeeded
        window.location.href = window.location.origin + '/stripe-success.php?order_ref=' + encodeURIComponent(orderRef);
      } else {
        setLoading(false);
      }
    }
  });

  function setLoading(isLoading) {
    if (isLoading) {
      submitButton.disabled = true;
      buttonText.textContent = 'Processing...';
    } else {
      submitButton.disabled = false;
      buttonText.textContent = 'Pay Rs. ' + document.querySelector('.summary-row.total span:last-child').textContent.replace('Rs. ', '');
    }
  }
})();
