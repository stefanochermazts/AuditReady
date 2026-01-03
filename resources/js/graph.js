import cytoscape from 'cytoscape';

// Make Cytoscape available globally for Livewire components
window.cytoscape = cytoscape;

// Provide an Alpine component for the audit relationship graph.
// This avoids putting large JS objects into `x-data=""` attributes (which can break parsing).
const registerAuditGraphComponent = () => {
    if (!window.Alpine) return;

    window.Alpine.data('auditGraph', () => ({
        nodes: [],
        edges: [],
        cy: null,
        isFullscreen: false,
        _onKeydown: null,

        init() {
            // Read JSON from base64-encoded data attributes on the root element.
            // This avoids any HTML / DOM morphing issues with <script> tags and keeps Alpine parsing safe.
            try {
                const nodesB64 = this.$el?.dataset?.nodesB64 || '';
                const edgesB64 = this.$el?.dataset?.edgesB64 || '';
                this.nodes = nodesB64 ? JSON.parse(atob(nodesB64)) : [];
                this.edges = edgesB64 ? JSON.parse(atob(edgesB64)) : [];
            } catch (e) {
                console.error('Failed to decode graph data:', e);
                this.nodes = [];
                this.edges = [];
            }

            if (typeof window.cytoscape === 'undefined') {
                this.showFallback();
                return;
            }

            this._onKeydown = (e) => {
                if (e.key === 'Escape' && this.isFullscreen) {
                    this.setFullscreen(false);
                }
            };
            window.addEventListener('keydown', this._onKeydown);

            this.$nextTick(() => this.initCytoscape());
        },

        setFullscreen(enabled) {
            this.isFullscreen = !!enabled;

            // Prevent background scroll while fullscreen.
            document.documentElement.style.overflow = this.isFullscreen ? 'hidden' : '';

            this.$nextTick(() => {
                if (this.cy) {
                    this.cy.resize();
                    this.cy.fit(undefined, 30);
                }
            });
        },

        toggleFullscreen() {
            this.setFullscreen(!this.isFullscreen);
        },

        initCytoscape() {
            const container = this.$refs.cytoscapeContainer;
            if (!container) return;

            try {
                const nodeIds = new Set(this.nodes.map((n) => String(n.id)));
                const filteredEdges = (this.edges || []).filter((e) => {
                    const from = String(e.from);
                    const to = String(e.to);
                    const ok = nodeIds.has(from) && nodeIds.has(to);
                    if (!ok) {
                        console.warn('Skipping edge with missing endpoint(s):', e, {
                            hasFrom: nodeIds.has(from),
                            hasTo: nodeIds.has(to),
                        });
                    }
                    return ok;
                });

                this.cy = window.cytoscape({
                    container,
                    elements: [
                        ...this.nodes.map((n) => ({
                            data: {
                                ...(n.data || {}),
                                // Ensure these keys can't be overwritten by `n.data` (which often includes `id`).
                                id: n.id,
                                label: n.label,
                                type: n.type,
                            },
                        })),
                        ...filteredEdges.map((e) => ({
                            data: {
                                id: `${e.from}_${e.to}`,
                                source: e.from,
                                target: e.to,
                                label: e.label,
                                type: e.type,
                            },
                        })),
                    ],
                    style: [
                        {
                            selector: 'node[type="audit"]',
                            style: {
                                'background-color': '#4F46E5',
                                label: 'data(label)',
                                color: '#111827',
                                'text-outline-color': '#ffffff',
                                'text-outline-width': 3,
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'font-size': '14px',
                                'font-weight': 'bold',
                                width: '80px',
                                height: '80px',
                                shape: 'round-rectangle',
                            },
                        },
                        {
                            selector: 'node[type="control"]',
                            style: {
                                'background-color': '#10B981',
                                label: 'data(label)',
                                color: '#111827',
                                'text-outline-color': '#ffffff',
                                'text-outline-width': 3,
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'font-size': '12px',
                                width: '60px',
                                height: '60px',
                                shape: 'round-rectangle',
                            },
                        },
                        {
                            selector: 'node[type="policy"]',
                            style: {
                                'background-color': '#8B5CF6',
                                label: 'data(label)',
                                color: '#111827',
                                'text-outline-color': '#ffffff',
                                'text-outline-width': 3,
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'font-size': '11px',
                                'text-wrap': 'wrap',
                                'text-max-width': '160px',
                                width: '58px',
                                height: '58px',
                                shape: 'round-tag',
                            },
                        },
                        {
                            selector: 'node[type="evidence"]',
                            style: {
                                'background-color': '#F59E0B',
                                label: 'data(label)',
                                color: '#111827',
                                'text-outline-color': '#ffffff',
                                'text-outline-width': 3,
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'font-size': '11px',
                                'text-wrap': 'wrap',
                                'text-max-width': '140px',
                                width: '50px',
                                height: '50px',
                                shape: 'ellipse',
                            },
                        },
                        {
                            selector: 'node[type="truncated"]',
                            style: {
                                'background-color': '#6B7280',
                                label: 'data(label)',
                                color: '#111827',
                                'text-outline-color': '#ffffff',
                                'text-outline-width': 3,
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'font-size': '10px',
                                width: '40px',
                                height: '40px',
                                shape: 'diamond',
                            },
                        },
                        {
                            selector: 'edge',
                            style: {
                                width: 2,
                                'line-color': '#9CA3AF',
                                'target-arrow-color': '#9CA3AF',
                                'target-arrow-shape': 'triangle',
                                'curve-style': 'bezier',
                                label: 'data(label)',
                                'font-size': '10px',
                                'text-rotation': 'autorotate',
                                'text-margin-y': -10,
                            },
                        },
                        {
                            selector: 'edge[type="audit_control"]',
                            style: {
                                'line-color': '#10B981',
                                'target-arrow-color': '#10B981',
                            },
                        },
                        {
                            selector: 'edge[type="audit_evidence"]',
                            style: {
                                'line-color': '#F59E0B',
                                'target-arrow-color': '#F59E0B',
                            },
                        },
                        {
                            selector: 'edge[type="evidence_control"]',
                            style: {
                                'line-color': '#EF4444',
                                'target-arrow-color': '#EF4444',
                                'line-style': 'dashed',
                            },
                        },
                        {
                            selector: 'edge[type="control_policy"]',
                            style: {
                                'line-color': '#8B5CF6',
                                'target-arrow-color': '#8B5CF6',
                                'line-style': 'dotted',
                            },
                        },
                    ],
                    layout: {
                        name: 'cose',
                        idealEdgeLength: 100,
                        nodeOverlap: 20,
                        refresh: 20,
                        fit: true,
                        padding: 30,
                        randomize: false,
                        componentSpacing: 100,
                        nodeRepulsion: 4000000,
                        edgeElasticity: 100,
                        nestingFactor: 5,
                        gravity: 80,
                        numIter: 1000,
                        initialTemp: 200,
                        coolingFactor: 0.95,
                        minTemp: 1.0,
                    },
                    minZoom: 0.1,
                    maxZoom: 4,
                    wheelSensitivity: 0.2,
                });

                this.cy.on('tap', 'node', (evt) => {
                    const node = evt.target;
                    const nodeId = node.id();
                    if (nodeId === 'truncated') return;
                    // Livewire is available as `$wire` in Alpine components.
                    this.$wire?.call('nodeClicked', nodeId);
                });

                this.cy.on('mouseover', 'node', (evt) => evt.target.style('opacity', 0.8));
                this.cy.on('mouseout', 'node', (evt) => evt.target.style('opacity', 1));
            } catch (error) {
                console.error('Failed to initialize Cytoscape:', error);
                this.showFallback();
            }
        },

        showFallback() {
            const container = this.$refs.cytoscapeContainer;
            if (!container) return;
            container.innerHTML = `
                <div class="flex items-center justify-center h-full p-8">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Graph visualization requires Cytoscape.js
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            Please ensure Cytoscape.js is loaded
                        </p>
                    </div>
                </div>
            `;
        },

        destroy() {
            if (this._onKeydown) {
                window.removeEventListener('keydown', this._onKeydown);
            }
            document.documentElement.style.overflow = '';
        },
    }));
};

// Register immediately if Alpine is already available, otherwise wait for init.
if (window.Alpine) {
    registerAuditGraphComponent();
} else {
    document.addEventListener('alpine:init', registerAuditGraphComponent);
}

export default cytoscape;
