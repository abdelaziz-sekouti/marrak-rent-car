// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      animation: {
        glow: 'glow 1.5s infinite', // Link the keyframes to an animation duration/iteration
      },
      keyframes: {
        glow: {
          '0%, 100%': { // Define the start and end states
            boxShadow: '0 0 10px rgba(255, 0, 255, 0.7)', // Example purple glow
          },
          '50%': { // Define the middle state for the pulsating effect
            boxShadow: '0 0 20px rgba(255, 0, 255, 1)',
          },
        },
      },
    },
  },
  plugins: [],
};