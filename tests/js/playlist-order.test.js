const { test, expect } = require('node:test');

test('playlist order payload', () => {
    const items = [
        { dataset: { id: '1' } },
        { dataset: { id: '2' } },
        { dataset: { id: '3' } },
    ];

    const payload = items.map((item, index) => ({
        id: item.dataset.id,
        position: index,
    }));

    expect(payload).toStrictEqual([
        { id: '1', position: 0 },
        { id: '2', position: 1 },
        { id: '3', position: 2 },
    ]);
});
