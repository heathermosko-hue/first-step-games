<?php
/**
 * Template: School District Package
 * Slug: school-district-package  (page ID 25776)
 */
get_header();
$BASE  = 'https://www.firststepreading.com/wp-content/uploads/2016/02/';
$_cart = home_url('/cart/');
$atc1  = $_cart . '?add-to-cart=1612';
?>
<style>
.sch{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;background:#FFFBF0;padding-bottom:3rem}
.sch-hero{background:linear-gradient(135deg,#1565C0,#3A8EF6);color:#fff;text-align:center;padding:3.5rem 1rem 5rem;position:relative;overflow:hidden}
.sch-hero h1{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:clamp(2rem,5vw,3rem);margin:0 0 .6rem;color:#fff}
.sch-hero p{font-size:1.1rem;opacity:.92;max-width:600px;margin:0 auto 1.6rem}
.sch-wave{position:absolute;bottom:0;left:0;width:100%;height:60px;display:block}
.sch-sec{padding:2.8rem 1rem;max-width:1100px;margin:0 auto}
.sch-title{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#1565C0;text-align:center;margin:0 0 .3rem}
.sch-sub{text-align:center;color:#555;font-size:1.05rem;margin:0 0 2.2rem}
/* feature grid */
.sch-feats{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.4rem}
.sch-feat{background:#fff;border-radius:20px;padding:1.6rem;box-shadow:0 5px 22px rgba(0,0,0,.08);border-top:4px solid #3A8EF6}
.sch-feat:nth-child(2){border-color:#1e8449}
.sch-feat:nth-child(3){border-color:#8B5CF6}
.sch-feat:nth-child(4){border-color:#FF8C42}
.sch-feat:nth-child(5){border-color:#0DBFBF}
.sch-feat:nth-child(6){border-color:#f59e0b}
.sch-feat-icon{font-size:2.4rem;margin-bottom:.6rem}
.sch-feat h3{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.15rem;color:#1A3A6B;margin:0 0 .4rem}
.sch-feat p{font-size:.9rem;color:#555;line-height:1.65;margin:0}
/* how it works */
.sch-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;counter-reset:sch-step}
.sch-step{background:#fff;border-radius:20px;padding:1.8rem 1.4rem;box-shadow:0 5px 22px rgba(0,0,0,.08);text-align:center;position:relative}
.sch-step-num{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#3A8EF6,#1565C0);color:#fff;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.6rem;display:inline-flex;align-items:center;justify-content:center;margin-bottom:.8rem;box-shadow:0 4px 14px rgba(58,142,246,.35)}
.sch-step h3{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.1rem;color:#1A3A6B;margin:0 0 .4rem}
.sch-step p{font-size:.88rem;color:#666;line-height:1.6;margin:0}
/* pricing */
.sch-price-box{background:#fff;border-radius:24px;box-shadow:0 8px 36px rgba(0,0,0,.10);overflow:hidden;max-width:600px;margin:0 auto}
.sch-price-head{background:linear-gradient(135deg,#1565C0,#3A8EF6);color:#fff;padding:2rem;text-align:center}
.sch-price-head h2{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.8rem;color:#fff;margin:0 0 .3rem}
.sch-price-head p{opacity:.9;margin:0;font-size:.95rem}
.sch-price-body{padding:2rem}
.sch-price-body ul{list-style:none;padding:0;margin:0 0 1.5rem}
.sch-price-body li{padding:.5rem 0;border-bottom:1px solid #f0f9ff;display:flex;gap:.6rem;align-items:flex-start;font-size:.95rem;color:#444}
.sch-price-body li::before{content:'✅';flex-shrink:0}
.sch-cta-btn{display:block;text-align:center;background:linear-gradient(135deg,#1565C0,#3A8EF6);color:#fff !important;border-radius:50px;padding:1rem 2rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.2rem;text-decoration:none;box-shadow:0 6px 22px rgba(58,142,246,.4);transition:transform .15s}
.sch-cta-btn:hover{transform:translateY(-3px)}
/* testimonial */
.sch-testi{background:#fff;border-radius:20px;padding:1.8rem;box-shadow:0 4px 20px rgba(0,0,0,.08);border-left:5px solid #3A8EF6;font-size:.95rem;color:#444;line-height:1.7;max-width:700px;margin:0 auto}
.sch-testi strong{color:#1565C0}
@media(max-width:768px){
  .sch-hero{padding:2.5rem 1rem 4rem}
  .sch-feats,.sch-steps{grid-template-columns:1fr}
}
</style>

<div class="sch">

  <!-- HERO -->
  <div class="sch-hero">
    <div style="font-size:3rem;margin-bottom:.5rem">🏫</div>
    <h1>First Step Reading for Schools &amp; Districts</h1>
    <p>Give every student in your classroom the gift of reading — with structured lessons, interactive games, and a teacher portal built for schools.</p>
    <div style="background:#fffbeb;border:2px solid #FFD93D;border-radius:16px;padding:1rem 1.6rem;max-width:600px;margin:.6rem auto 0;text-align:center">
      <div style="font-weight:700;color:#92400e;margin-bottom:.3rem">&#x2b50; Special Pricing Available</div>
      <p style="color:#78350f;font-size:.9rem;margin:0">Discounts for school districts, multi-school purchases, non-profits, and multi-year subscriptions. Contact <a href="mailto:schools@firststepreading.com" style="color:#1565C0;font-weight:700">schools@firststepreading.com</a> to learn more.</p>
    </div>
    <svg class="sch-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60" preserveAspectRatio="none"><path fill="#FFFBF0" d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z"/></svg>
  </div>

  <!-- TRUST STATS -->
  <div style="background:#fff;padding:1.8rem 1rem;border-bottom:1px solid #f0f9ff">
    <div style="max-width:900px;margin:0 auto;display:flex;flex-wrap:wrap;justify-content:center;gap:2rem;text-align:center">
      <div><div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#1565C0">2010</div><div style="font-size:.85rem;color:#666">Teaching Since</div></div>
      <div><div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#1e8449">50+</div><div style="font-size:.85rem;color:#666">Video Lessons</div></div>
      <div><div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#8B5CF6">25+</div><div style="font-size:.85rem;color:#666">Online Games</div></div>
      <div><div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#FF8C42">Grade 1</div><div style="font-size:.85rem;color:#666">Reading Level Achieved</div></div>
    </div>
  </div>

  <!-- WHAT SCHOOLS GET -->
  <div class="sch-sec">
    <div class="sch-title">🎒 What Your School Gets</div>
    <div class="sch-sub">Everything your classroom needs — ready to use from day one.</div>
    <div class="sch-feats">
      <div class="sch-feat"><div class="sch-feat-icon">🍎</div><h3>Teacher Portal</h3><p>Create and manage your class, add students, and access all lessons from one simple dashboard — any time.</p></div>
      <div class="sch-feat"><div class="sch-feat-icon">📹</div><h3>50+ Video Lessons</h3><p>Stream lessons on your classroom whiteboard or let students watch on individual devices. Commercial-free, always available.</p></div>
      <div class="sch-feat"><div class="sch-feat-icon">🎮</div><h3>25+ Reading Games</h3><p>Engaging phonics and sight word games students can play independently — great for centers or early finishers.</p></div>
      <div class="sch-feat"><div class="sch-feat-icon">📚</div><h3>Digital Books</h3><p>Levelled digital readers matched to each lesson so students can practise right away.</p></div>
      <div class="sch-feat"><div class="sch-feat-icon">🧩</div><h3>Phonics + Sight Words</h3><p>Systematic phonics instruction paired with high-frequency sight words — the research-backed combination that works.</p></div>
      <div class="sch-feat"><div class="sch-feat-icon">⏱️</div><h3>Self-Paced</h3><p>Every student goes at their own pace. Perfect for differentiating instruction across a mixed-ability classroom.</p></div>
    </div>
  </div>

  <!-- HOW IT WORKS -->
  <div style="background:#eff6ff;padding:3rem 1rem">
    <div style="max-width:1100px;margin:0 auto">
      <div class="sch-title">📋 How It Works in Your Classroom</div>
      <div class="sch-sub">Up and running in minutes — no IT setup, no installs.</div>
      <div class="sch-steps">
        <div class="sch-step"><div class="sch-step-num">1</div><h3>Get Access</h3><p>Purchase school access and receive your teacher login credentials by email the same day.</p></div>
        <div class="sch-step"><div class="sch-step-num">2</div><h3>Set Up Your Class</h3><p>Log in to the teacher portal, create your class, and add your students — takes about 5 minutes.</p></div>
        <div class="sch-step"><div class="sch-step-num">3</div><h3>Start Teaching</h3><p>Stream lesson videos on your whiteboard, assign games for independent practice, and track who's done what.</p></div>
        <div class="sch-step"><div class="sch-step-num">4</div><h3>Watch Them Read</h3><p>Students work through phonics, sight words, and levelled books at their own pace — with built-in reinforcement at every step.</p></div>
      </div>
    </div>
  </div>

  <!-- TEACHER TESTIMONIAL -->
  <div class="sch-sec" style="padding-bottom:1rem">
    <div class="sch-testi">
      ⭐⭐⭐⭐⭐<br><br>
      "My students absolutely love the videos and games. I use First Step Reading for whole-class lessons on the whiteboard and for independent centre time. In one semester I saw <strong>every student</strong> improve their reading level. It's the most engaging program I've used in 15 years of teaching."<br><br>
      <strong>— Ms. Tremblay, Grade 1 Teacher</strong>
    </div>
  </div>

  <!-- PRICING -->
  <div class="sch-sec" id="sch-contact">
    <div class="sch-title">&#x1f4b0; School Pricing</div>
    <div class="sch-sub">Simple, transparent pricing for classrooms, schools, and districts.</div>

    <!-- INCLUDED WITH EVERY SCHOOL PACKAGE -->
    <div style="background:#eff6ff;border-radius:20px;padding:1.8rem 2rem;max-width:860px;margin:0 auto 2.5rem">
      <div style="font-size:1.2rem;font-weight:700;color:#1565C0;margin-bottom:1rem;text-align:center">Included With Every School Package</div>
      <div style="display:flex;flex-wrap:wrap;gap:.6rem .8rem;justify-content:center">
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; Teacher dashboard</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; Student account management</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; Class progress tracking</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; 180+ digital reading books</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; 50+ video lessons</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; 25+ learning games</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; Printable worksheets &amp; activities</span>
        <span style="background:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.92rem;color:#1A3A6B">&#x2705; School-wide implementation support</span>
      </div>
    </div>

    <!-- 3-TIER PRICING CARDS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.6rem;max-width:960px;margin:0 auto">

      <!-- SINGLE TEACHER -->
      <div style="background:#fff;border-radius:24px;box-shadow:0 8px 36px rgba(0,0,0,.10);overflow:hidden;display:flex;flex-direction:column">
        <div style="background:linear-gradient(135deg,#3A8EF6,#1565C0);padding:1.8rem 1.5rem;text-align:center;color:#fff">
          <div style="font-size:2.4rem;margin-bottom:.4rem">&#x1f469;&#x200d;&#x1f3eb;</div>
          <h2 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.5rem;color:#fff;margin:0 0 .3rem">Single Teacher License</h2>
          <p style="opacity:.9;font-size:.92rem;margin:0 0 .8rem">Up to 35 students</p>
          <div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2.8rem;color:#fff">$99<span style="font-size:1.1rem;opacity:.85">/year</span></div>
        </div>
        <div style="padding:1.5rem;flex:1;display:flex;flex-direction:column">
          <ul style="list-style:none;padding:0;margin:0 0 1.4rem;flex:1">
            <li style="padding:.4rem 0;border-bottom:1px solid #eff6ff;font-size:.93rem;color:#444">&#x2705; All program features included</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #eff6ff;font-size:.93rem;color:#444">&#x2705; One teacher account</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #eff6ff;font-size:.93rem;color:#444">&#x2705; Up to 35 student logins</li>
            <li style="padding:.4rem 0;font-size:.93rem;color:#444">&#x2705; Full year access</li>
          </ul>
          <a href="https://www.firststepreading.com/checkout/?add-to-cart=28201" style="display:block;text-align:center;background:linear-gradient(135deg,#3A8EF6,#1565C0);color:#fff !important;border-radius:50px;padding:.9rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.05rem;text-decoration:none">🛒 Buy Now — $99/year</a>
        </div>
      </div>

      <!-- SCHOOL LICENSE -->
      <div style="background:#fff;border-radius:24px;box-shadow:0 12px 44px rgba(30,132,73,.2);overflow:hidden;display:flex;flex-direction:column;transform:scale(1.03);position:relative">
        <div style="position:absolute;top:16px;right:-12px;background:linear-gradient(135deg,#FF8C42,#FFD93D);color:#5a3400;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:.8rem;padding:.3rem 1.4rem .3rem 1rem;border-radius:50px 0 0 50px;font-weight:900;box-shadow:0 3px 10px rgba(255,140,66,.4)">&#x2b50; Most Popular</div>
        <div style="background:linear-gradient(135deg,#1e8449,#27ae60);padding:1.8rem 1.5rem;text-align:center;color:#fff">
          <div style="font-size:2.4rem;margin-bottom:.4rem">&#x1f3eb;</div>
          <h2 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.5rem;color:#fff;margin:0 0 .3rem">School License</h2>
          <p style="opacity:.9;font-size:.92rem;margin:0 0 .8rem">Unlimited teachers &amp; students</p>
          <div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2.8rem;color:#fff">$499<span style="font-size:1.1rem;opacity:.85">/year</span></div>
        </div>
        <div style="padding:1.5rem;flex:1;display:flex;flex-direction:column">
          <ul style="list-style:none;padding:0;margin:0 0 1.4rem;flex:1">
            <li style="padding:.4rem 0;border-bottom:1px solid #f0fdf4;font-size:.93rem;color:#444">&#x2705; All program features included</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #f0fdf4;font-size:.93rem;color:#444">&#x2705; Unlimited teacher accounts</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #f0fdf4;font-size:.93rem;color:#444">&#x2705; Unlimited student logins</li>
            <li style="padding:.4rem 0;font-size:.93rem;color:#444">&#x2705; Full year access</li>
          </ul>
          <a href="https://www.firststepreading.com/checkout/?add-to-cart=28202" style="display:block;text-align:center;background:linear-gradient(135deg,#1e8449,#27ae60);color:#fff !important;border-radius:50px;padding:.9rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.05rem;text-decoration:none">🛒 Buy Now — $499/year</a>
        </div>
      </div>

      <!-- DISTRICT LICENSE -->
      <div style="background:#fff;border-radius:24px;box-shadow:0 8px 36px rgba(0,0,0,.10);overflow:hidden;display:flex;flex-direction:column">
        <div style="background:linear-gradient(135deg,#4B0082,#8B5CF6);padding:1.8rem 1.5rem;text-align:center;color:#fff">
          <div style="font-size:2.4rem;margin-bottom:.4rem">&#x1f30e;</div>
          <h2 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.5rem;color:#fff;margin:0 0 .3rem">District License</h2>
          <p style="opacity:.9;font-size:.92rem;margin:0 0 .8rem">Multiple schools, centralized admin</p>
          <div style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#fff;margin-top:.5rem">Custom Pricing</div>
        </div>
        <div style="padding:1.5rem;flex:1;display:flex;flex-direction:column">
          <ul style="list-style:none;padding:0;margin:0 0 1.4rem;flex:1">
            <li style="padding:.4rem 0;border-bottom:1px solid #f5f3ff;font-size:.93rem;color:#444">&#x2705; All program features included</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #f5f3ff;font-size:.93rem;color:#444">&#x2705; Multiple schools in one account</li>
            <li style="padding:.4rem 0;border-bottom:1px solid #f5f3ff;font-size:.93rem;color:#444">&#x2705; Centralized district reporting</li>
            <li style="padding:.4rem 0;font-size:.93rem;color:#444">&#x2705; Volume discounts available</li>
          </ul>
          <a href="mailto:schools@firststepreading.com" style="display:block;text-align:center;background:linear-gradient(135deg,#4B0082,#8B5CF6);color:#fff !important;border-radius:50px;padding:.9rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.05rem;text-decoration:none">&#x1f4e7; Contact for District Pricing</a>
          <p style="text-align:center;font-size:.82rem;color:#999;margin-top:.7rem">schools@firststepreading.com</p>
        </div>
      </div>

    </div>

  </div>

  <!-- FINAL CTA -->
  <div style="background:linear-gradient(135deg,#1565C0,#3A8EF6);color:#fff;padding:3rem 1rem;text-align:center">
    <h2 style="font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:2rem;color:#fff;margin:0 0 .5rem">🚀 Ready to Bring First Step Reading to Your School?</h2>
    <p style="opacity:.9;max-width:500px;margin:0 auto 1.5rem">Contact us today and we'll get your class set up and reading in no time.</p>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" style="display:inline-block;background:#fff;color:#1565C0 !important;border-radius:50px;padding:.9rem 2.2rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;font-size:1.1rem;text-decoration:none;font-weight:900;box-shadow:0 4px 18px rgba(0,0,0,.2);margin:.3rem">📧 Get in Touch</a>
  </div>

</div>
<?php get_footer(); ?>
