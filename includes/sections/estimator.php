<section class="estimator" id="estimator">
  <div class="estimator-header">
    <div class="section-label" style="justify-content:center">Plan Your Budget</div>
    <h2 class="section-title reveal" style="text-align:center">Event <em>Cost Estimator</em></h2>
    <p class="estimator-intro reveal">Build a planning estimate, then carry the same package and add-ons into your inquiry.</p>
  </div>
  <div class="estimator-container reveal">
    <div class="estimator-controls">
      <div class="est-group">
        <label for="estPackage">Select Base Package</label>
        <select id="estPackage">
          <option value="ripple">Ripple Pack (PHP 45,000)</option>
          <option value="crest" selected>Crest Pack (PHP 85,000)</option>
          <option value="sovereign">Sovereign Wave (PHP 150,000)</option>
        </select>
      </div>
      <div class="est-group">
        <label for="estGuests">Estimated Guest Count: <span id="guestCountLabel">200</span></label>
        <input type="range" id="estGuests" min="50" max="2500" step="50" value="100" class="est-range">
        <div id="estGuestError" class="validation-msg" style="display:none; color:var(--red); font-size:11px; margin-top:8px;"></div>
      </div>
      <div class="est-group">
        <label>Optional Add-ons</label>
        <div class="est-grid">
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="5000" value="Extra Event Hour"><span class="est-checkmark"></span> Extra Event Hour (PHP 5,000)</label>
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="8000" value="3-Tier Wedding Cake"><span class="est-checkmark"></span> 3-Tier Wedding Cake (PHP 8,000)</label>
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="12000" value="Full Event Coordination"><span class="est-checkmark"></span> Full Event Coordination (PHP 12,000)</label>
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="15000" value="Pro A/V Upgrade"><span class="est-checkmark"></span> Pro A/V Upgrade (PHP 15,000)</label>
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="10000" value="Flower Wall Backdrop"><span class="est-checkmark"></span> Flower Wall Backdrop (PHP 10,000)</label>
          <label class="est-check-container"><input type="checkbox" class="est-addon" data-price="25000" value="Overnight Accommodation"><span class="est-checkmark"></span> Overnight Accommodation (PHP 25,000)</label>
        </div>
      </div>
    </div>

    <div class="estimator-result">
      <div class="total-box">
        <div class="total-label">Estimated Grand Total</div>
        <div class="total-price" id="totalPrice">PHP 85,000</div>
        <div class="total-sub">Planning estimates may change after final brief review.</div>
      </div>
      <div class="total-breakdown">
        <h3>Planning Summary</h3>
        <p id="estSummary">Crest Pack (200 Guests)</p>
        <button class="btn-primary" style="width:100%; margin-top:20px;" onclick="closePanelGoTo('contact')">Carry This Plan to Inquiry</button>
      </div>
    </div>
  </div>
</section>
