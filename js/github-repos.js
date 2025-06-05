// portfolio/js/github-repos.js

document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('repo-carousel');
  const username = 'henrymoran';

  try {
    const res = await fetch(`https://api.github.com/users/${plobethus}/repos?per_page=100`);
    if (!res.ok) throw new Error('Failed to fetch repos');
    const repos = await res.json();

    const selected = repos
      .filter(repo => !repo.fork && !repo.private)
      .sort((a, b) => b.stargazers_count - a.stargazers_count)
      .slice(0, 6);

    selected.forEach(repo => {
      const card = document.createElement('div');
      card.className = 'repo-card';
      card.innerHTML = `
        <h3><a href="${repo.html_url}" target="_blank" rel="noopener noreferrer">${repo.name}</a></h3>
        <p>${repo.description || 'No description provided.'}</p>
        <p><strong>⭐ ${repo.stargazers_count}</strong></p>
      `;
      container.appendChild(card);
    });
  } catch (err) {
    console.error('GitHub API error:', err);
    container.innerHTML = '<p>Unable to load repositories at this time.</p>';
  }
});
