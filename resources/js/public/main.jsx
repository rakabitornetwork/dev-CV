import { createRoot } from 'react-dom/client';
import App from './App';

const root = document.getElementById('cv-root');

if (root) {
    createRoot(root).render(<App cv={window.__CV__ ?? {}} />);
}
