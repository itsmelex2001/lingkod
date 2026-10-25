<script>
  import Hero from './components/landing/Hero.svelte';
  import Services from './components/landing/Services.svelte';
  import About from './components/landing/About.svelte';
  import Location from './components/landing/Location.svelte';
  import Footer from './components/layout/Footer.svelte';
  import Chatbot from './components/chatbot/Chatbot.svelte';
</script>

<main class="svelte-app-container">
  <Hero />
  <Services />
  <About />
  <Location />
  <Footer />
  <Chatbot />
</main>

<style>
  :global(html) {
    scroll-behavior: smooth;
  }

  :global(body) {
    margin: 0;
    padding: 0;
    background-color: #f8faf9;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: #333;
    overflow-x: hidden;
  }

  .svelte-app-container {
    width: 100%;
    display: flex;
    flex-direction: column;
  }
</style>
