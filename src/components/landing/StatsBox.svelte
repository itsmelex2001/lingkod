<script>
  export let stats = {
    appointmentsBooked: "0",
    slotsAvailable: "0",
    registeredPatients: "0"
  };
</script>

<div class="stats-box-wrapper">
  <div class="stats-box">
    <div class="stat-item">
      <h3 class="stat-number">{stats.appointmentsBooked}</h3>
      <p class="stat-label">Appointments booked</p>
    </div>
    <div class="stat-item">
      <h3 class="stat-number">{stats.slotsAvailable}</h3>
      <p class="stat-label">Slot schedules available</p>
    </div>
    <div class="stat-item">
      <h3 class="stat-number">{stats.registeredPatients}</h3>
      <p class="stat-label">Registered patients</p>
    </div>
  </div>
</div>

<style>
  .stats-box-wrapper {
    position: absolute;
    bottom: -60px;
    width: 100%;
    display: flex;
    justify-content: center;
    z-index: 10;
    padding: 0 20px;
    box-sizing: border-box;
  }

  .stats-box {
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: #ffffff;
    border-radius: 16px;
    padding: 24px 30px;
    width: 100%;
    max-width: 900px;
    box-shadow: 0 14px 35px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(0, 0, 0, 0.04);
  }

  .stat-item {
    text-align: center;
    flex: 1;
    border-right: 1px solid #edf2f7;
  }

  .stat-item:last-child {
    border-right: none;
  }

  .stat-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: #0f4c25;
    margin: 0 0 4px 0;
    line-height: 1;
    font-family: 'Montserrat', sans-serif;
  }

  .stat-label {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
    font-weight: 500;
  }

  @media (max-width: 768px) {
    .stats-box {
      flex-direction: column;
      gap: 16px;
      padding: 20px;
    }
    .stat-item {
      border-right: none;
      border-bottom: 1px solid #edf2f7;
      padding-bottom: 12px;
      width: 100%;
    }
    .stat-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
  }
</style>
