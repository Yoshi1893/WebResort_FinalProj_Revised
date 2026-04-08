<section class="contact" id="contact">
  <div class="contact-inner">
    <div class="contact-info">
      <div class="section-label">Get In Touch</div>
      <h2 class="section-title">Begin Your <em>Inquiry</em> With Us</h2>
      <p>Sign in, share your event details, and our team will shape the right proposal for your celebration.</p>
      <div class="contact-details">
        <div class="contact-item">
          <div class="contact-icon">📍</div>
          <div>
            <div class="contact-item-label">Location</div>
            <div class="contact-item-value">123 Mulawin St, Poblacion, Bustos, Bulacan</div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-icon">📞</div>
          <div>
            <div class="contact-item-label">Phone</div>
            <div class="contact-item-value">+63 917 123 4567 | +63 2 8765 4321</div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-icon">✉️</div>
          <div>
            <div class="contact-item-label">Email</div>
            <div class="contact-item-value">events@9waveseventsplace.com</div>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-icon">🕐</div>
          <div>
            <div class="contact-item-label">Office Hours</div>
            <div class="contact-item-value">Monday - Sunday, 9:00 AM - 7:00 PM</div>
          </div>
        </div>
      </div>
    </div>
    <div class="contact-form">
      <div class="wizard-container" id="bookingWizard">
        <p class="wizard-note">You can complete the inquiry details here, but sign in will be required before final submission.</p>
        <div class="wizard-progress">
          <div class="progress-bar-inner" id="wizardBar" style="width:25%"></div>
          <div class="progress-steps">
            <div class="p-step active" data-step="1">1</div>
            <div class="p-step" data-step="2">2</div>
            <div class="p-step" data-step="3">3</div>
            <div class="p-step" data-step="4">4</div>
          </div>
        </div>

        <div class="wizard-step active" id="wizardStep1">
          <h3 class="wizard-subtitle">Step 1: Choose Your <em>Space</em></h3>
          <div class="venue-cards triple-grid" style="margin-bottom:30px;">
            <div class="wizard-venue-card active" data-venue="Pearl Ballroom">
              <div class="venue-card-img" style="background:url('image/ballroomVenue.jpg') center/cover"></div>
              <div class="venue-card-info">
                <h4>Pearl Ballroom</h4>
                <p>Grand Luxury | 500 Guests</p>
              </div>
            </div>
            <div class="wizard-venue-card" data-venue="Wavecrest Garden">
              <div class="venue-card-img" style="background:url('image/gardenWedding.jpg') center/cover"></div>
              <div class="venue-card-info">
                <h4>Wavecrest Garden</h4>
                <p>Natural Elegance | 300 Guests</p>
              </div>
            </div>
            <div class="wizard-venue-card" data-venue="Tidal Pool Terrace">
              <div class="venue-card-img" style="background:url('image/poolVenue.jpg') center/cover"></div>
              <div class="venue-card-info">
                <h4>Tidal Pool Terrace</h4>
                <p>Poolside Bliss | 150 Guests</p>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="wizEvent">What are we celebrating?</label>
            <select id="wizEvent" class="wizard-select">
              <option value="Wedding" selected>Wedding</option>
              <option value="Debut">Debut / 18th Birthday</option>
              <option value="Corporate Gala">Corporate Gala</option>
              <option value="Social Event">Social Event</option>
            </select>
          </div>
        </div>

        <div class="wizard-step" id="wizardStep2">
          <h3 class="wizard-subtitle">Step 2: Shape Your <em>Package</em></h3>
          <p style="margin-bottom:20px; font-size:14px; color:var(--text-light)">Use the same package, guest count, and add-ons from the estimator or refine them here.</p>
          <div class="form-group">
            <label for="wizPackage">Base Package</label>
            <select id="wizPackage" class="wizard-select">
              <option value="ripple">Ripple Pack</option>
              <option value="crest" selected>Crest Pack</option>
              <option value="sovereign">Sovereign Wave</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="wizGuests">Estimated Guest Count</label>
              <input type="text" id="wizGuests" inputmode="numeric" pattern="[0-9]*" placeholder="Minimum 50 guests">
              <div id="wizGuestError" class="validation-msg" style="display:none; color:var(--red); font-size:11px; margin-top:8px;"></div>
            </div>
            <div class="form-group">
              <label for="wizBudget">Planning Budget Range</label>
              <select id="wizBudget" class="wizard-select">
                <option value="Under PHP 75,000">Under PHP 75,000</option>
                <option value="PHP 75,000 - PHP 150,000" selected>PHP 75,000 - PHP 150,000</option>
                <option value="PHP 150,000 - PHP 250,000">PHP 150,000 - PHP 250,000</option>
                <option value="Above PHP 250,000">Above PHP 250,000</option>
              </select>
            </div>
          </div>
          <div class="extras-grid">
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="5000" value="Extra Event Hour"><div class="extra-box"><span class="extra-icon">+</span><div class="extra-text"><strong>Extra Event Hour</strong><span>Extend your celebration timeline</span></div></div></label>
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="8000" value="3-Tier Wedding Cake"><div class="extra-box"><span class="extra-icon">CK</span><div class="extra-text"><strong>3-Tier Wedding Cake</strong><span>Classic celebration centerpiece</span></div></div></label>
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="12000" value="Full Event Coordination"><div class="extra-box"><span class="extra-icon">CO</span><div class="extra-text"><strong>Full Event Coordination</strong><span>Planning support from program to flow</span></div></div></label>
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="15000" value="Pro A/V Upgrade"><div class="extra-box"><span class="extra-icon">AV</span><div class="extra-text"><strong>Pro A/V Upgrade</strong><span>Enhanced audio, projection, and stage support</span></div></div></label>
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="10000" value="Flower Wall Backdrop"><div class="extra-box"><span class="extra-icon">FL</span><div class="extra-text"><strong>Flower Wall Backdrop</strong><span>Styled focal wall for portraits and entrances</span></div></div></label>
            <label class="extra-item"><input type="checkbox" class="wiz-addon" data-price="25000" value="Overnight Accommodation"><div class="extra-box"><span class="extra-icon">ST</span><div class="extra-text"><strong>Overnight Accommodation</strong><span>Overnight support for key hosts or VIPs</span></div></div></label>
          </div>
        </div>

        <div class="wizard-step" id="wizardStep3">
          <h3 class="wizard-subtitle">Step 3: Share Your <em>Dates</em></h3>
          <p style="margin-bottom:20px; font-size:14px; color:var(--text-light)">Give us your preferred date plus one backup option so we can review the best fit faster.</p>
          <div class="form-row">
            <div class="form-group"><label for="wizPreferredDate">Preferred Date</label><input type="text" id="wizPreferredDate" placeholder="Select your first-choice date" readonly></div>
            <div class="form-group"><label for="wizBackupDate">Backup Date</label><input type="text" id="wizBackupDate" placeholder="Select your backup date" readonly></div>
          </div>
          <div class="form-group"><label for="wizNotes">Anything the team should know?</label><textarea id="wizNotes" rows="4" placeholder="Tell us about your preferred flow, style, or non-negotiables."></textarea></div>
        </div>

        <div class="wizard-step" id="wizardStep4">
          <h3 class="wizard-subtitle">Step 4: Review and <em>Send Inquiry</em></h3>
          <div id="wizardSummary" class="wizard-summary-box"></div>
          <div class="wizard-auth-note" id="wizardAuthNote">Sign in with your 9 Waves account before sending this inquiry.</div>
          <div class="wizard-form-fields">
            <div class="form-row">
              <div class="form-group"><label for="wizFirst">First Name</label><input type="text" id="wizFirst" placeholder="Maria" required></div>
              <div class="form-group"><label for="wizLast">Last Name</label><input type="text" id="wizLast" placeholder="Santos" required></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label for="wizEmail">Email Address</label><input type="email" id="wizEmail" placeholder="maria@email.com" required></div>
              <div class="form-group"><label for="wizPhone">Phone Number</label><input type="tel" id="wizPhone" placeholder="+63 917 000 0000" required></div>
            </div>
          </div>
        </div>

        <div class="wizard-nav">
          <button class="btn-outline" id="wizPrev" style="display:none; padding:12px 25px;">Back</button>
          <button class="auth-btn" id="wizNext" style="margin-left:auto; padding:12px 25px;">Next Step</button>
          <button class="auth-btn" id="wizSubmit" style="display:none; margin-left:auto; padding:12px 25px;">Send Inquiry</button>
        </div>
      </div>
    </div>
  </div>
</section>
