<script>
  export let logoSrc = "/sdn/public/assets/LINKod_logo.png"; // Changed to cleaner logo if available, or fallback to original
</script>

<header class="hero">
  <div class="hero-content">
    <img src="/sdn/public/assets/linkod-logo.png.png" alt="LINKod Logo" class="hero-logo-img" draggable="false">

    <h1>Mariveles Mental Wellness and General Hospital</h1>
    <h2>Localized Network and Knowledge-base for Online Service Delivery</h2>
    <p>
      Dedicated to your health, driven by excellence. Your seamless journey to professional care starts here.
    </p>
  </div>
</header>

<style>
  .hero {
    min-height: calc(100vh - 70px);
    background: url('/sdn/public/assets/bg_login.jpg') center/cover fixed;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }

  .hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(30, 70, 45, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    z-index: 1;
  }

  .hero-content {
    position: relative;
    z-index: 10;
    text-align: center;
    width: 100%;
    max-width: 1100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: fadeIn 1s ease-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .hero-logo-img {
    max-width: 350px;
    width: 100%;
    height: auto;
    margin-bottom: 0.5rem;
    filter: drop-shadow(0 4px 15px rgba(255, 255, 255, 0.6)) drop-shadow(0 0 35px rgba(255, 255, 255, 0.4)) brightness(1.3);
    transform: scale(1.4);
    user-select: none;
    -webkit-user-drag: none;
  }

  h1 {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(1.5rem, 3.8vw, 2.6rem);
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.2rem;
    letter-spacing: -0.02em;
    text-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
    width: 100%;
  }

  h2 {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(1.2rem, 2vw, 1.6rem);
    font-weight: 500;
    color: #bbf7d0; /* Soft emerald */
    margin-bottom: 0.8rem;
    letter-spacing: 0.01em;
  }

  p {
    font-family: 'Inter', sans-serif;
    font-size: 1.15rem;
    color: #e2e8f0;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }

  .btn-primary {
    background: #10b981; /* Emerald green */
    color: white;
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
  }

  .btn-primary:hover {
    background: #059669;
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4);
  }

  .btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(5px);
  }

  .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: white;
    transform: translateY(-3px);
  }
</style>
