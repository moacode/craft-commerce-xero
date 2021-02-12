/**
 * Control Panel specific JS
 *
 * @author Josh Smith <by@joshthe.dev>
 */

const connectionSelect = document.getElementById('connectionId');
const disconnectButton = document.querySelector('.js--xero-disconnect');
const connectButton = document.querySelector('.js--xero-connect');

/**
 * Attaches a change event listener to a connections select menu
 * On change, a PATCH request is sent to the server which updates he selected connection
 * On successful response, the page is reloaded so the new connection's config is loaded
 */
if (connectionSelect != null) {
    connectionSelect.addEventListener('change', () => {
      Craft.cp.displayNotice('Loading...');
      updateSelectedConnection(connectionSelect.value)
        .then(response => response.json())
        .then(() => window.location.reload());
    });
}

/**
 * Disconnects a Xero connection
 */
if (disconnectButton != null) {
  disconnectButton.addEventListener('click', () => {
    if (confirm('Are you sure you want to disconnect this Xero organisation?')) {
      Craft.cp.displayNotice('Disconnecting...');
      disconnectXero(disconnectButton.dataset.connectionid)
        .then(response => response.json())
        .then(() => window.location.reload());
    }
  });
}

if (connectButton != null) {
  connectButton.addEventListener('click', () => {
    Craft.cp.displayNotice('Loading...');
  });
}

/**
 * Fires off a request to update the selected connection
 *
 * @param  int connectionId ID of the connection to select
 *
 * @return Promise
 */
function updateSelectedConnection(connectionId)
{
  return fetch(Craft.getCpUrl('xero/connections/update'), {
    method: 'PATCH',
    body: JSON.stringify({
        connectionId: connectionId,
        [Craft.csrfTokenName]: Craft.csrfTokenValue
    }),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
  });
}

/**
 * Fires off a request to disconnect the current connection
 *
 * @param  int connectionId ID of the connection to disconnect
 *
 * @return Promise
 */
function disconnectXero(connectionId)
{
  return fetch(Craft.getCpUrl('xero/connections/disconnect'), {
    method: 'POST',
    body: JSON.stringify({
        connectionId: connectionId,
        [Craft.csrfTokenName]: Craft.csrfTokenValue
    }),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
  });
}
