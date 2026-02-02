<?php
// eSewa Frontend Integration Functions
require_once __DIR__ . '/esewa_payment.inc.php';

/**
 * Generate payment gateway selection HTML
 * @param string $selected_gateway Currently selected gateway
 * @return string HTML for gateway selection
 */
function generatePaymentGatewaySelection($selected_gateway = 'esewa') {
    $gateways = getAvailablePaymentGateways();
    $html = '<div class="payment-gateway-selection mb-3">';
    $html .= '<label class="form-label fw-bold">Select Payment Method:</label>';
    $html .= '<div class="row">';
    
    foreach ($gateways as $gateway) {
        $gateway_id = $gateway . '_gateway';
        $checked = ($selected_gateway === $gateway) ? 'checked' : '';
        $active_class = ($selected_gateway === $gateway) ? 'border-primary bg-light' : '';
        
        $html .= '<div class="col-md-6 mb-2">';
        $html .= '<div class="card payment-gateway-card ' . $active_class . '" data-gateway="' . $gateway . '">';
        $html .= '<div class="card-body p-3">';
        $html .= '<div class="form-check">';
        $html .= '<input class="form-check-input" type="radio" name="payment_gateway" id="' . $gateway_id . '" value="' . $gateway . '" ' . $checked . '>';
        $html .= '<label class="form-check-label w-100" for="' . $gateway_id . '">';
        
        if ($gateway === 'esewa') {
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<div class="gateway-icon me-2">';
            $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
            $html .= '<rect width="24" height="24" rx="4" fill="#00A652"/>';
            $html .= '<text x="12" y="16" text-anchor="middle" fill="white" font-family="Arial" font-size="8" font-weight="bold">eSewa</text>';
            $html .= '</svg>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="fw-bold">eSewa</div>';
            $html .= '<small class="text-muted">Pay with eSewa Wallet</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if ($gateway === 'mock') {
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<div class="gateway-icon me-2">';
            $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
            $html .= '<rect width="24" height="24" rx="4" fill="#FF6B35"/>';
            $html .= '<text x="12" y="16" text-anchor="middle" fill="white" font-family="Arial" font-size="6" font-weight="bold">TEST</text>';
            $html .= '</svg>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="fw-bold">Mock Payment</div>';
            $html .= '<small class="text-muted">For testing only</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</label>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Add JavaScript for gateway selection
    $html .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const gatewayCards = document.querySelectorAll(".payment-gateway-card");
        const gatewayRadios = document.querySelectorAll("input[name=\'payment_gateway\']");
        
        gatewayCards.forEach(function(card) {
            card.addEventListener("click", function() {
                const gateway = this.dataset.gateway;
                const radio = document.getElementById(gateway + "_gateway");
                
                // Remove active class from all cards
                gatewayCards.forEach(function(c) {
                    c.classList.remove("border-primary", "bg-light");
                });
                
                // Add active class to selected card
                this.classList.add("border-primary", "bg-light");
                
                // Check the radio button
                radio.checked = true;
                
                // Submit the form
                radio.form.submit();
            });
        });
        
        gatewayRadios.forEach(function(radio) {
            radio.addEventListener("change", function() {
                console.log("Radio button changed to:", this.value);
                // Submit the form when radio is changed
                this.form.submit();
            });
        });
        
        // Add form submission debugging
        const paymentForm = document.getElementById("payment-form");
        if (paymentForm) {
            paymentForm.addEventListener("submit", function(e) {
                console.log("Form submitted!");
                console.log("Form action:", paymentForm.action);
                console.log("Form method:", paymentForm.method);
                console.log("Form data:");
                
                // Log all form fields
                const formData = new FormData(paymentForm);
                for (let [key, value] of formData.entries()) {
                    console.log(key + ":", value);
                }
            });
        }
    });
    </script>';
    
    return $html;
}

/**
 * Generate payment form for selected gateway
 * @param string $gateway Payment gateway (esewa)
 * @param array $payment_data Payment data
 * @return string HTML form or payment button
 */
function generatePaymentForm($gateway, $payment_data) {
    switch ($gateway) {
        case 'esewa':
            return generateEsewaPaymentForm($payment_data);
        case 'mock':
            return generateMockPaymentForm($payment_data);
        default:
            return '<div class="alert alert-danger">Invalid payment gateway selected</div>';
    }
}

/**
 * Generate eSewa payment form with modern styling
 * @param array $payment_data Payment data from initiateEsewaPayment
 * @return string HTML form
 */
function generateEsewaPaymentForm($payment_data) {
    $form_data = $payment_data['form_data'];
    $credentials_used = $payment_data['credentials_used'] ?? 'Default credentials';
    $payment_url = $payment_data['payment_url'];
    
    $html = '<div class="esewa-payment-container">';
    $html .= '<div class="alert alert-info">';
    $html .= '<h5 class="alert-heading"><i class="fas fa-lock me-2"></i>Secure Payment Redirect</h5>';
    $html .= '<p class="mb-2">You will be redirected to eSewa\'s secure payment gateway to complete your payment.</p>';
    $html .= '<p class="mb-0"><strong>Amount:</strong> NPR ' . number_format($form_data['amount'], 2) . '</p>';
    $html .= '</div>';
    
    $html .= '<div class="text-center mb-4">';
    $html .= '<div class="spinner-border text-primary" role="status">';
    $html .= '<span class="visually-hidden">Loading...</span>';
    $html .= '</div>';
    $html .= '<p class="mt-2 text-muted">Preparing secure payment...</p>';
    $html .= '</div>';
    
    $html .= '<form id="esewaPaymentForm" method="POST" action="' . htmlspecialchars($payment_url) . '" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8">';
    
    foreach ($form_data as $key => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
    }
    
    $html .= '<div class="d-grid gap-2 d-md-flex justify-content-md-center">';
    $html .= '<button type="submit" class="btn btn-success btn-lg px-5" id="manualSubmitBtn">';
    $html .= '<i class="fas fa-wallet me-2"></i>Pay with eSewa';
    $html .= '</button>';
    $html .= '<button type="button" class="btn btn-outline-secondary px-4" onclick="location.reload()">';
    $html .= '<i class="fas fa-redo me-2"></i>Cancel';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</form>';
    $html .= '</div>';
    
    // Add clean JavaScript for auto-submit
    $html .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("esewaPaymentForm");
        
        // Auto-submit after 2 seconds for better UX
        setTimeout(function() {
            if (form) {
                form.submit();
            }
        }, 2000);
        
        // Manual submit fallback
        form.addEventListener("submit", function(e) {
            const submitBtn = document.getElementById("manualSubmitBtn");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = \'<i class="fas fa-spinner fa-spin me-2"></i>Redirecting...\';
            }
        });
                console.log("🔄 Manual eSewa V2 submission triggered");
                
                if (form.checkValidity()) {
                    console.log("✅ Form validation passed");
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = \'<i class="fas fa-spinner fa-spin me-2"></i>Submitting...\';
                    
                    // Log before submission
                    console.log("📋 About to submit form with data:");
                    const finalData = new FormData(form);
                    for (let [key, value] of finalData.entries()) {
                        console.log("📤 " + key + ":", value);
                    }
                    
                    // Create a new form to submit to eSewa (prevents interference)
                    const tempForm = document.createElement("form");
                    tempForm.method = "POST";
                    tempForm.action = form.action;
                    tempForm.target = "_blank";
                    tempForm.style.display = "none";
                    
                    // Copy all form fields
                    for (let [key, value] of finalData.entries()) {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = key;
                        input.value = value;
                        tempForm.appendChild(input);
                    }
                    
                    // Add to page and submit
                    document.body.appendChild(tempForm);
                    console.log("🚀 Submitting to eSewa:", tempForm.action);
                    tempForm.submit();
                    
                    // Clean up
                    setTimeout(() => {
                        document.body.removeChild(tempForm);
                    }, 1000);
                    
                } else {
                    console.error("❌ Form validation failed");
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = \'<i class="fas fa-wallet me-2"></i>Pay with eSewa V2\';
                }
            });
        }
    });
    </script>';
    
    return $html;
}

/**
 * Generate payment status display
 * @param string $payment_id Payment ID
 * @return string HTML status display
 */
function generatePaymentStatus($payment_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT status, payment_gateway, amount, created_at FROM payments WHERE payment_id = ?");
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    
    if (!$payment) {
        return '<div class="alert alert-warning">Payment not found</div>';
    }
    
    $status_class = '';
    $status_icon = '';
    
    switch ($payment['status']) {
        case 'completed':
            $status_class = 'success';
            $status_icon = 'fa-check-circle';
            break;
        case 'pending':
            $status_class = 'warning';
            $status_icon = 'fa-clock';
            break;
        case 'failed':
            $status_class = 'danger';
            $status_icon = 'fa-times-circle';
            break;
        default:
            $status_class = 'secondary';
            $status_icon = 'fa-question-circle';
    }
    
    $html = '<div class="payment-status-container">';
    $html .= '<div class="alert alert-' . $status_class . '">';
    $html .= '<div class="d-flex align-items-center">';
    $html .= '<i class="fas ' . $status_icon . ' me-3 fs-4"></i>';
    $html .= '<div>';
    $html .= '<h6 class="alert-heading mb-1">Payment Status: ' . ucfirst($payment['status']) . '</h6>';
    $html .= '<p class="mb-1"><strong>Gateway:</strong> ' . ucfirst($payment['payment_gateway']) . '</p>';
    $html .= '<p class="mb-1"><strong>Amount:</strong> NPR ' . number_format($payment['amount'], 2) . '</p>';
    $html .= '<p class="mb-0"><small class="text-muted">Payment ID: ' . $payment_id . '</small></p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Get available payment gateways
 * @return array List of available payment gateways
 */
function getAvailablePaymentGateways() {
    $gateways = ['esewa'];
    
    // Add mock payment option for testing
    if (defined('ENABLE_MOCK_PAYMENT') && ENABLE_MOCK_PAYMENT) {
        $gateways[] = 'mock';
    }
    
    return $gateways;
}

/**
 * Get payment gateway configuration for frontend
 * @return array Gateway configuration
 */
function getPaymentGatewayConfig() {
    return [
        'available_gateways' => getAvailablePaymentGateways(),
        'default_gateway' => 'esewa',
        'esewa_config' => getEsewaConfig()
    ];
}
?>
