<script>
  export let welcomeText = 'Welcome to';
  export let tagline = 'Seamless healthcare access and delivery.';
</script>

<div class="brand-showcase">
  <h1>{welcomeText}</h1>
  <img src="/sdn/public/assets/linkod_brush.png" alt="LINGKOD" class="linkod-brush" />
  <p>{tagline}</p>
</div>

<style>
  .brand-showcase {
    position: relative;
    z-index: 5;
    color: #ffffff;
    max-width: 480px;
  }

  .brand-showcase h1 {
    font-size: 2.2rem;
    font-weight: 300;
    margin: 0 0 10px 0;
    letter-spacing: 1px;
  }

  .linkod-brush {
    width: 280px;
    height: auto;
    filter: drop-shadow(0 4px 15px rgba(0, 0, 0, 0.4));
    margin-bottom: 16px;
  }

  .brand-showcase p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #e2e8f0;
    font-weight: 400;
  }

  @media (max-width: 900px) {
    .brand-showcase {
      display: none;
    }
  }
</style>
