// portfolio/js/profile.js

document.addEventListener('DOMContentLoaded', () => {
    const photos = [
      '/images/profile/profile1.jpg',
      '/images/profile/profile2.jpg',
      '/images/profile/profile3.jpg',
    ];

    let index = 0;
    const img = document.getElementById('profile-photo');

    setInterval(() => {
      index = (index + 1) % photos.length;
      img.src = photos[index];
    }, 10000); // 10 seconds
  });